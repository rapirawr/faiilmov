@if(\App\Services\AdService::isSlotEnabled('popunder'))
    <!-- Adsterra Popunder / OnClick Script -->
    {!! \App\Services\AdService::getSlotCode('popunder') !!}
@endif
