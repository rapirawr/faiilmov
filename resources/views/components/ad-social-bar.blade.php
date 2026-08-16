@if(\App\Services\AdService::isSlotEnabled('socialbar'))
    <!-- Adsterra Social Bar / In-Page Push Script -->
    {!! \App\Services\AdService::getSlotCode('socialbar') !!}
@endif
