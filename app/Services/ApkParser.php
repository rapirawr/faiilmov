<?php

namespace App\Services;

use ZipArchive;

class ApkParser
{
    /**
     * Parse APK file to extract versionName and versionCode (build_number).
     *
     * @param string $apkPath Absolute path to .apk file
     * @return array{version_name: string|null, build_number: int|null}
     */
    public static function parse(string $apkPath): array
    {
        $result = [
            'version_name' => null,
            'build_number' => null,
        ];

        if (!file_exists($apkPath)) {
            return $result;
        }

        // Method 1: Try AAPT tool if available (Android SDK)
        $aaptResult = static::parseWithAapt($apkPath);
        if ($aaptResult['version_name'] !== null || $aaptResult['build_number'] !== null) {
            return $aaptResult;
        }

        // Method 2: Pure PHP AXML Binary Manifest Parser
        return static::parsePurePhp($apkPath);
    }

    /**
     * Parse APK using AAPT CLI tool if installed/available in Android SDK.
     */
    private static function parseWithAapt(string $apkPath): array
    {
        $versionName = null;
        $buildNumber = null;

        $aaptExecutable = null;

        // Check system PATH
        @exec('aapt version 2>&1', $out, $code);
        if ($code === 0) {
            $aaptExecutable = 'aapt';
        } else {
            // Check Windows Android SDK default paths
            $sdkBuildTools = glob('C:/Android/Sdk/build-tools/*/aapt.exe');
            if (!empty($sdkBuildTools)) {
                $aaptExecutable = end($sdkBuildTools);
            }
        }

        if ($aaptExecutable) {
            $cmd = escapeshellarg($aaptExecutable) . ' dump badging ' . escapeshellarg($apkPath);
            @exec($cmd, $outputLines, $returnCode);

            if ($returnCode === 0 && !empty($outputLines)) {
                $fullOutput = implode("\n", $outputLines);
                // Match package: name='...' versionCode='1' versionName='1.0.0'
                if (preg_match("/versionCode='(\d+)'/", $fullOutput, $mCode)) {
                    $buildNumber = (int) $mCode[1];
                }
                if (preg_match("/versionName='([^']+)'/", $fullOutput, $mName)) {
                    $versionName = trim($mName[1]);
                }
            }
        }

        return [
            'version_name' => $versionName,
            'build_number' => $buildNumber,
        ];
    }

    /**
     * Parse APK using pure PHP AXML (Android Binary XML) parsing.
     */
    private static function parsePurePhp(string $apkPath): array
    {
        $versionName = null;
        $buildNumber = null;

        if (!class_exists(ZipArchive::class)) {
            return compact('versionName', 'buildNumber');
        }

        $zip = new ZipArchive();
        if ($zip->open($apkPath) !== true) {
            return compact('versionName', 'buildNumber');
        }

        $manifestContent = $zip->getFromName('AndroidManifest.xml');
        $zip->close();

        if (!$manifestContent || strlen($manifestContent) < 12) {
            return compact('versionName', 'buildNumber');
        }

        // Extract String Pool
        $strings = static::extractStringPool($manifestContent);

        // Find string pool index for 'versionName' and 'versionCode'
        $versionNameIdx = array_search('versionName', $strings, true);
        $versionCodeIdx = array_search('versionCode', $strings, true);

        $length = strlen($manifestContent);

        // Scan binary XML nodes
        for ($i = 8; $i <= $length - 20; $i += 4) {
            $chunk = @unpack('V5', substr($manifestContent, $i, 20));
            if (!$chunk || count($chunk) < 5) continue;

            $nameIdx     = $chunk[2];
            $rawValueIdx = $chunk[3];
            $dataType    = ($chunk[4] >> 16) & 0xFF;
            $dataVal     = $chunk[5];

            // Check for versionName attribute
            if ($versionNameIdx !== false && $nameIdx === $versionNameIdx && $versionName === null) {
                if ($rawValueIdx !== 0xFFFFFFFF && isset($strings[$rawValueIdx])) {
                    $versionName = $strings[$rawValueIdx];
                } elseif (($dataType === 0x03) && isset($strings[$dataVal])) {
                    $versionName = $strings[$dataVal];
                }
            }

            // Check for versionCode attribute
            if ($versionCodeIdx !== false && $nameIdx === $versionCodeIdx && $buildNumber === null) {
                if ($dataType >= 0x10 && $dataType <= 0x1F) {
                    $buildNumber = (int) $dataVal;
                } elseif ($dataVal > 0 && $dataVal < 10000000) {
                    $buildNumber = (int) $dataVal;
                }
            }

            if ($versionName !== null && $buildNumber !== null) {
                break;
            }
        }

        // Fallback: If versionName wasn't matched via attribute name index, check string pool for semver string
        if ($versionName === null) {
            foreach ($strings as $str) {
                if (is_string($str) && preg_match('/^\d+\.\d+(\.\d+)?$/', trim($str))) {
                    $versionName = trim($str);
                    break;
                }
            }
        }

        return [
            'version_name' => $versionName,
            'build_number' => $buildNumber,
        ];
    }

    /**
     * Helper to extract String Pool from AXML.
     */
    private static function extractStringPool(string $content): array
    {
        $strings = [];
        $offset = 8;
        $length = strlen($content);

        while ($offset < $length - 8) {
            $chunk = @unpack('vtype/vheaderSize/VchunkSize', substr($content, $offset, 8));
            if (!$chunk || $chunk['chunkSize'] <= 0) break;

            if ($chunk['type'] === 0x0001) { // String Pool Chunk
                $sp = @unpack('VstringCount/VstyleCount/Vflags/VstringStart/VstylesStart', substr($content, $offset + 8, 20));
                if (!$sp) break;

                $stringCount = $sp['stringCount'];
                $flags = $sp['flags'];
                $isUtf8 = ($flags & (1 << 8)) !== 0;

                $offsetsOffset = $offset + 28;
                $stringDataStart = $offset + $sp['stringStart'];

                $offsets = @unpack("V{$stringCount}", substr($content, $offsetsOffset, $stringCount * 4));
                if (!$offsets) break;

                foreach ($offsets as $strOffset) {
                    $pos = $stringDataStart + $strOffset;
                    if ($pos >= $length) continue;

                    if ($isUtf8) {
                        $u8len = ord($content[$pos] ?? "\0");
                        if ($u8len & 0x80) {
                            $pos++;
                        }
                        $pos++;
                        $u8charLen = ord($content[$pos] ?? "\0");
                        if ($u8charLen & 0x80) {
                            $pos++;
                        }
                        $pos++;
                        $str = substr($content, $pos, $u8charLen);
                    } else {
                        $u16len = @unpack('v', substr($content, $pos, 2))[1] ?? 0;
                        if ($u16len & 0x8000) {
                            $pos += 2;
                        }
                        $pos += 2;
                        $rawStr = substr($content, $pos, $u16len * 2);
                        $str = @mb_convert_encoding($rawStr, 'UTF-8', 'UTF-16LE');
                    }
                    $strings[] = $str;
                }
                break;
            }

            $offset += $chunk['chunkSize'];
        }

        return $strings;
    }
}
