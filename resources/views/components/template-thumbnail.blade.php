@props(['src'])

{{--
    Live miniature of an invoice template.

    The iframe always lays the invoice out at a fixed A4 page width (800px), then is scaled
    down to whatever width the card actually has. Scaling relative to the card (the old
    "w-[400%] scale-[0.25]" trick) made the invoice reflow/crop whenever cards were narrower
    than ~200px — i.e. on mobile, tablets and the onboarding grid.

    The parent must be `relative` with a fixed aspect ratio (e.g. aspect-[21/29.7]).
--}}
<div data-template-thumb class="absolute inset-0 overflow-hidden bg-white pointer-events-none select-none">
    <iframe src="{{ $src }}" loading="lazy" tabindex="-1" aria-hidden="true" scrolling="no"
        style="position:absolute;top:0;left:0;width:800px;height:1132px;border:0;transform-origin:0 0;transform:scale(0.25);"></iframe>
</div>

@once
    <script>
        (function () {
            var PAGE_WIDTH = 800;

            function fit(el) {
                var frame = el.querySelector('iframe');
                if (frame && el.clientWidth > 0) {
                    frame.style.transform = 'scale(' + (el.clientWidth / PAGE_WIDTH) + ')';
                }
            }

            var observer = 'ResizeObserver' in window
                ? new ResizeObserver(function (entries) { entries.forEach(function (e) { fit(e.target); }); })
                : null;

            function init() {
                document.querySelectorAll('[data-template-thumb]').forEach(function (el) {
                    if (el.dataset.thumbReady) return;
                    el.dataset.thumbReady = '1';
                    fit(el);
                    if (observer) observer.observe(el);
                });
            }

            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', init);
            } else {
                init();
            }
            if (!observer) window.addEventListener('resize', init);
        })();
    </script>
@endonce
