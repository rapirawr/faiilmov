<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PageElement;
use App\Models\AdminActivityLog;
use App\Services\PageElementService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AdminPageElementController extends Controller
{
    protected PageElementService $elementService;

    public function __construct(PageElementService $elementService)
    {
        $this->elementService = $elementService;
    }

    /**
     * Display Visual Element Studio & management page
     */
    public function index(Request $request)
    {
        $typeFilter = $request->query('type', 'all');
        $statusFilter = $request->query('status', 'all');
        $search = $request->query('search', '');

        $query = PageElement::orderBy('order', 'asc')->orderBy('created_at', 'desc');

        if ($typeFilter !== 'all') {
            $query->where('type', $typeFilter);
        }

        if ($statusFilter === 'active') {
            $query->where('is_active', true);
        } elseif ($statusFilter === 'inactive') {
            $query->where('is_active', false);
        }

        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('title', 'like', "%{$search}%")
                  ->orWhere('content', 'like', "%{$search}%");
            });
        }

        $elements = $query->get();
        $presets = PageElement::getPresets();

        $stats = [
            'total'           => PageElement::count(),
            'active'          => PageElement::where('is_active', true)->count(),
            'broadcast_bars'  => PageElement::where('type', 'broadcast_bar')->count(),
            'floating_widgets'=> PageElement::where('type', 'floating_widget')->count(),
            'popup_modals'    => PageElement::where('type', 'popup_modal')->count(),
            'promo_banners'   => PageElement::where('type', 'promo_banner')->count(),
            'custom_blocks'   => PageElement::where('type', 'custom_block')->count(),
        ];

        return view('admin.elements.index', compact('elements', 'presets', 'stats', 'typeFilter', 'statusFilter', 'search'));
    }

    /**
     * Store a newly created element
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name'                   => 'required|string|max:255',
            'type'                   => 'required|in:broadcast_bar,floating_widget,popup_modal,custom_block,promo_banner',
            'title'                  => 'nullable|string|max:255',
            'content'                => 'nullable|string',
            'image_url'              => 'nullable|string|max:1000',
            'button_text'            => 'nullable|string|max:100',
            'button_url'             => 'nullable|string|max:1000',
            'button_target'          => 'nullable|in:_self,_blank',
            'icon'                   => 'nullable|string|max:50',
            'position'               => 'nullable|string|max:50',
            'theme_color'            => 'nullable|string|max:50',
            'custom_css'             => 'nullable|string',
            'custom_html'            => 'nullable|string',
            'target_page'            => 'required|string|max:50',
            'target_path_pattern'    => 'nullable|string|max:255',
            'target_device'          => 'required|in:all,desktop,mobile',
            'target_audience'        => 'required|in:all,guest,user',
            'is_dismissible'         => 'nullable|boolean',
            'dismiss_duration_hours' => 'nullable|integer',
            'order'                  => 'nullable|integer',
            'is_active'              => 'nullable|boolean',
            'starts_at'              => 'nullable|date',
            'ends_at'                => 'nullable|date|after_or_equal:starts_at',
        ]);

        if ($validator->fails()) {
            if ($request->wantsJson()) {
                return response()->json(['status' => 'error', 'errors' => $validator->errors()], 422);
            }
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $data = $validator->validated();
        $data['is_dismissible'] = $request->has('is_dismissible');
        $data['is_active'] = $request->has('is_active');
        $data['button_target'] = $data['button_target'] ?? '_self';

        $element = PageElement::create($data);
        $this->elementService->clearCache();

        AdminActivityLog::log(
            'created_page_element',
            "Membuat elemen '{$element->name}' (Tipe: {$element->type_label})",
            'PageElement',
            $element->id
        );

        if ($request->wantsJson()) {
            return response()->json([
                'status' => 'success',
                'message' => "Elemen '{$element->name}' berhasil dibuat!",
                'element' => $element,
            ]);
        }

        return redirect()->route('admin.page_elements.index')->with('success', "Elemen '{$element->name}' berhasil ditambahkan ke website!");
    }

    /**
     * Update the specified element
     */
    public function update(Request $request, PageElement $pageElement)
    {
        $validator = Validator::make($request->all(), [
            'name'                   => 'required|string|max:255',
            'type'                   => 'required|in:broadcast_bar,floating_widget,popup_modal,custom_block,promo_banner',
            'title'                  => 'nullable|string|max:255',
            'content'                => 'nullable|string',
            'image_url'              => 'nullable|string|max:1000',
            'button_text'            => 'nullable|string|max:100',
            'button_url'             => 'nullable|string|max:1000',
            'button_target'          => 'nullable|in:_self,_blank',
            'icon'                   => 'nullable|string|max:50',
            'position'               => 'nullable|string|max:50',
            'theme_color'            => 'nullable|string|max:50',
            'custom_css'             => 'nullable|string',
            'custom_html'            => 'nullable|string',
            'target_page'            => 'required|string|max:50',
            'target_path_pattern'    => 'nullable|string|max:255',
            'target_device'          => 'required|in:all,desktop,mobile',
            'target_audience'        => 'required|in:all,guest,user',
            'is_dismissible'         => 'nullable|boolean',
            'dismiss_duration_hours' => 'nullable|integer',
            'order'                  => 'nullable|integer',
            'is_active'              => 'nullable|boolean',
            'starts_at'              => 'nullable|date',
            'ends_at'                => 'nullable|date|after_or_equal:starts_at',
        ]);

        if ($validator->fails()) {
            if ($request->wantsJson()) {
                return response()->json(['status' => 'error', 'errors' => $validator->errors()], 422);
            }
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $data = $validator->validated();
        $data['is_dismissible'] = $request->has('is_dismissible');
        $data['is_active'] = $request->has('is_active');
        $data['button_target'] = $data['button_target'] ?? '_self';

        $pageElement->update($data);
        $this->elementService->clearCache();

        AdminActivityLog::log(
            'updated_page_element',
            "Memperbarui konfigurasi elemen '{$pageElement->name}'",
            'PageElement',
            $pageElement->id
        );

        if ($request->wantsJson()) {
            return response()->json([
                'status' => 'success',
                'message' => "Elemen '{$pageElement->name}' berhasil diperbarui!",
                'element' => $pageElement,
            ]);
        }

        return redirect()->route('admin.page_elements.index')->with('success', "Elemen '{$pageElement->name}' berhasil diperbarui!");
    }

    /**
     * Quick toggle active status via AJAX
     */
    public function toggle(Request $request, PageElement $pageElement)
    {
        $pageElement->is_active = !$pageElement->is_active;
        $pageElement->save();

        $this->elementService->clearCache();

        AdminActivityLog::log(
            'toggled_page_element',
            "Mengubah status elemen '{$pageElement->name}' menjadi " . ($pageElement->is_active ? 'AKTIF' : 'NONAKTIF'),
            'PageElement',
            $pageElement->id
        );

        return response()->json([
            'status' => 'success',
            'is_active' => $pageElement->is_active,
            'message' => "Status '{$pageElement->name}' sekarang " . ($pageElement->is_active ? 'Aktif' : 'Nonaktif') . ".",
        ]);
    }

    /**
     * Delete the specified element
     */
    public function destroy(PageElement $pageElement)
    {
        $name = $pageElement->name;
        $pageElement->delete();

        $this->elementService->clearCache();

        AdminActivityLog::log(
            'deleted_page_element',
            "Menghapus elemen '{$name}'",
            'PageElement',
            null
        );

        return redirect()->route('admin.page_elements.index')->with('success', "Elemen '{$name}' berhasil dihapus!");
    }
}
