@if ($paginator->hasPages())
<nav style="display: flex; align-items: center; justify-content: center; gap: 4px; padding: 16px;">
    {{-- Previous Button --}}
    @if ($paginator->onFirstPage())
        <span style="padding: 8px 14px; border-radius: 8px; border: 1px solid var(--border); color: var(--text-secondary); cursor: not-allowed; font-size: 13px; opacity: 0.5; user-select: none;">
            ← Prev
        </span>
    @else
        <a href="{{ $paginator->previousPageUrl() }}" rel="prev"
           style="padding: 8px 14px; border-radius: 8px; border: 1px solid var(--border); color: var(--text); font-size: 13px; text-decoration: none; transition: all 0.2s; hover:border-color: var(--primary); hover:color: var(--primary);">
            ← Prev
        </a>
    @endif

    {{-- Page Numbers --}}
    @php
        $currentPage = $paginator->currentPage();
        $lastPage = $paginator->lastPage();
        $pages = [];

        if ($lastPage <= 7) {
            $pages = range(1, $lastPage);
        } else {
            $pages[] = 1;
            if ($currentPage > 3) {
                $pages[] = '...';
            }
            $start = max(2, $currentPage - 1);
            $end = min($lastPage - 1, $currentPage + 1);
            foreach (range($start, $end) as $i) {
                $pages[] = $i;
            }
            if ($currentPage < $lastPage - 2) {
                $pages[] = '...';
            }
            $pages[] = $lastPage;
        }
    @endphp

    @foreach($pages as $page)
        @if($page === '...')
            <span style="padding: 8px 10px; font-size: 13px; color: var(--text-secondary);">…</span>
        @elseif($page == $currentPage)
            <span style="padding: 8px 14px; border-radius: 8px; background: var(--primary); color: white; font-size: 13px; font-weight: 600; cursor: default;">
                {{ $page }}
            </span>
        @else
            <a href="{{ $paginator->url($page) }}"
               style="padding: 8px 14px; border-radius: 8px; border: 1px solid var(--border); color: var(--text); font-size: 13px; text-decoration: none; transition: all 0.2s;">
                {{ $page }}
            </a>
        @endif
    @endforeach

    {{-- Next Button --}}
    @if ($paginator->hasMorePages())
        <a href="{{ $paginator->nextPageUrl() }}" rel="next"
           style="padding: 8px 14px; border-radius: 8px; border: 1px solid var(--border); color: var(--text); font-size: 13px; text-decoration: none; transition: all 0.2s;">
            Next →
        </a>
    @else
        <span style="padding: 8px 14px; border-radius: 8px; border: 1px solid var(--border); color: var(--text-secondary); cursor: not-allowed; font-size: 13px; opacity: 0.5; user-select: none;">
            Next →
        </span>
    @endif
</nav>
@endif
