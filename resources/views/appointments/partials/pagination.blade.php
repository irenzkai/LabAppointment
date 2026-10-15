@if ($paginator->hasPages())
    @php
        $currentPage = $paginator->currentPage();
        $lastPage = $paginator->lastPage();
        $pageName = $paginator->getPageName();
    @endphp

    <nav aria-label="Appointments Pagination">
        <ul class="pagination pagination-sm m-0 align-items-center gap-1">
            {{-- Previous Page Link (<) --}}
            @if ($paginator->onFirstPage())
                <li class="page-item disabled" aria-disabled="true" aria-label="&laquo; Previous">
                    <span class="page-link" aria-hidden="true">&lsaquo;</span>
                </li>
            @else
                <li class="page-item">
                    <a class="page-link" href="{{ $paginator->previousPageUrl() }}" rel="prev" aria-label="&laquo; Previous">&lsaquo;</a>
                </li>
            @endif

            {{-- Numeric Links with Typeable "..." Jump Input --}}
            @if ($lastPage <= 6)
                {{-- If total pages is small, render all page numbers directly --}}
                @for ($i = 1; $i <= $lastPage; $i++)
                    @if ($i == $currentPage)
                        <li class="page-item active" aria-current="page"><span class="page-link">{{ $i }}</span></li>
                    @else
                        <li class="page-item"><a class="page-link" href="{{ $paginator->url($i) }}">{{ $i }}</a></li>
                    @endif
                @endfor
            @else
                {{-- Sliding window when pages exceed 6 --}}
                @if ($currentPage <= 4)
                    {{-- 1 2 3 4 ... Last --}}
                    @for ($i = 1; $i <= 4; $i++)
                        @if ($i == $currentPage)
                            <li class="page-item active" aria-current="page"><span class="page-link">{{ $i }}</span></li>
                        @else
                            <li class="page-item"><a class="page-link" href="{{ $paginator->url($i) }}">{{ $i }}</a></li>
                        @endif
                    @endfor

                    {{-- Typeable "..." Jump Input --}}
                    <li class="page-item jump-input-item mx-0.5">
                        <input type="number" 
                               min="1" 
                               max="{{ $lastPage }}" 
                               class="form-control form-control-sm text-center queue-jump-input shadow-none" 
                               placeholder="..." 
                               title="Type any page number (1-{{ $lastPage }}) and press Enter" 
                               data-page-name="{{ $pageName }}" 
                               data-max-page="{{ $lastPage }}" 
                               onkeydown="if(event.key==='Enter'){ event.preventDefault(); window.jumpToQueuePage(this); }" 
                               onblur="if(this.value.trim() !== ''){ window.jumpToQueuePage(this); }">
                    </li>

                    {{-- Last Page --}}
                    <li class="page-item"><a class="page-link" href="{{ $paginator->url($lastPage) }}">{{ $lastPage }}</a></li>

                @elseif ($currentPage > 4 && $currentPage < $lastPage - 3)
                    {{-- 1 ... (current-1) current (current+1) ... Last --}}
                    <li class="page-item"><a class="page-link" href="{{ $paginator->url(1) }}">1</a></li>

                    <li class="page-item jump-input-item mx-0.5">
                        <input type="number" 
                               min="1" 
                               max="{{ $lastPage }}" 
                               class="form-control form-control-sm text-center queue-jump-input shadow-none" 
                               placeholder="..." 
                               title="Type any page number (1-{{ $lastPage }}) and press Enter" 
                               data-page-name="{{ $pageName }}" 
                               data-max-page="{{ $lastPage }}" 
                               onkeydown="if(event.key==='Enter'){ event.preventDefault(); window.jumpToQueuePage(this); }" 
                               onblur="if(this.value.trim() !== ''){ window.jumpToQueuePage(this); }">
                    </li>

                    @for ($i = $currentPage - 1; $i <= $currentPage + 1; $i++)
                        @if ($i == $currentPage)
                            <li class="page-item active" aria-current="page"><span class="page-link">{{ $i }}</span></li>
                        @else
                            <li class="page-item"><a class="page-link" href="{{ $paginator->url($i) }}">{{ $i }}</a></li>
                        @endif
                    @endfor

                    <li class="page-item jump-input-item mx-0.5">
                        <input type="number" 
                               min="1" 
                               max="{{ $lastPage }}" 
                               class="form-control form-control-sm text-center queue-jump-input shadow-none" 
                               placeholder="..." 
                               title="Type any page number (1-{{ $lastPage }}) and press Enter" 
                               data-page-name="{{ $pageName }}" 
                               data-max-page="{{ $lastPage }}" 
                               onkeydown="if(event.key==='Enter'){ event.preventDefault(); window.jumpToQueuePage(this); }" 
                               onblur="if(this.value.trim() !== ''){ window.jumpToQueuePage(this); }">
                    </li>

                    <li class="page-item"><a class="page-link" href="{{ $paginator->url($lastPage) }}">{{ $lastPage }}</a></li>

                @else
                    {{-- 1 ... (Last-3) (Last-2) (Last-1) Last --}}
                    <li class="page-item"><a class="page-link" href="{{ $paginator->url(1) }}">1</a></li>

                    <li class="page-item jump-input-item mx-0.5">
                        <input type="number" 
                               min="1" 
                               max="{{ $lastPage }}" 
                               class="form-control form-control-sm text-center queue-jump-input shadow-none" 
                               placeholder="..." 
                               title="Type any page number (1-{{ $lastPage }}) and press Enter" 
                               data-page-name="{{ $pageName }}" 
                               data-max-page="{{ $lastPage }}" 
                               onkeydown="if(event.key==='Enter'){ event.preventDefault(); window.jumpToQueuePage(this); }" 
                               onblur="if(this.value.trim() !== ''){ window.jumpToQueuePage(this); }">
                    </li>

                    @for ($i = $lastPage - 3; $i <= $lastPage; $i++)
                        @if ($i == $currentPage)
                            <li class="page-item active" aria-current="page"><span class="page-link">{{ $i }}</span></li>
                        @else
                            <li class="page-item"><a class="page-link" href="{{ $paginator->url($i) }}">{{ $i }}</a></li>
                        @endif
                    @endfor
                @endif
            @endif

            {{-- Next Page Link (>) --}}
            @if ($paginator->hasMorePages())
                <li class="page-item">
                    <a class="page-link" href="{{ $paginator->nextPageUrl() }}" rel="next" aria-label="Next &raquo;">&rsaquo;</a>
                </li>
            @else
                <li class="page-item disabled" aria-disabled="true" aria-label="Next &raquo;">
                    <span class="page-link" aria-hidden="true">&rsaquo;</span>
                </li>
            @endif
        </ul>
    </nav>
@endif