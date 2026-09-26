@foreach($blocks as $block)
    @php 
        $data = $block['data'] ?? []; 
        $spacingTop = ($data['spacing']['top'] ?? 16) * 0.25;
        $spacingBottom = ($data['spacing']['bottom'] ?? 16) * 0.25;
    @endphp
    
    @switch($block['type'])
        @case('hero')
            <section class="px-4" style="background-color: {{ $data['bgColor'] ?? '#0ea5e9' }}; color: {{ $data['textColor'] ?? '#ffffff' }}; padding-top: {{ $spacingTop }}rem; padding-bottom: {{ $spacingBottom }}rem;">
                <div class="max-w-4xl mx-auto text-center">
                    <h1 class="text-4xl md:text-5xl font-bold mb-6">{{ $data['title'] ?? '' }}</h1>
                    <p class="text-xl mb-8 opacity-90">{{ $data['subtitle'] ?? '' }}</p>
                    @if(!empty($data['buttonText']))
                        <a href="{{ $data['buttonUrl'] ?? '#' }}" class="inline-block px-8 py-3 bg-white text-gray-900 font-semibold rounded-lg hover:bg-gray-100 transition">
                            {{ $data['buttonText'] }}
                        </a>
                    @endif
                </div>
            </section>
            @break

        @case('heading')
            @php
                $headingLevel = $data['level'] ?? 'h2';
                $headingFontFamily = $data['fontFamily'] ?? 'Inter';
                $headingFontSize = $data['fontSize'] ?? null;
                $headingFontWeight = $data['fontWeight'] ?? 'bold';
                $headingColor = $data['color'] ?? '#111827';
                $headingLetterSpacing = $data['letterSpacing'] ?? 0;
                $headingLineHeight = $data['lineHeight'] ?? '1.25';
                $headingUppercase = $data['uppercase'] ?? false;
                $headingItalic = $data['italic'] ?? false;
                $headingUnderline = $data['underline'] ?? false;
                
                $defaultSizes = ['h1' => 36, 'h2' => 30, 'h3' => 24, 'h4' => 20, 'h5' => 18, 'h6' => 16];
                $fontSize = $headingFontSize ?: ($defaultSizes[$headingLevel] ?? 24);
                
                $weightMap = ['normal' => 400, 'medium' => 500, 'semibold' => 600, 'bold' => 700, 'extrabold' => 800];
                $fontWeight = $weightMap[$headingFontWeight] ?? 700;
                
                $headingStyle = "font-family: '{$headingFontFamily}', sans-serif; font-size: {$fontSize}px; font-weight: {$fontWeight}; color: {$headingColor}; letter-spacing: {$headingLetterSpacing}px; line-height: {$headingLineHeight};";
                if ($headingUppercase) $headingStyle .= " text-transform: uppercase;";
                if ($headingItalic) $headingStyle .= " font-style: italic;";
                if ($headingUnderline) $headingStyle .= " text-decoration: underline;";
            @endphp
            <div class="max-w-4xl mx-auto px-4 text-{{ $data['align'] ?? 'center' }}" style="padding-top: {{ $spacingTop }}rem; padding-bottom: {{ $spacingBottom }}rem;">
                <{{ $headingLevel }} style="{{ $headingStyle }}">{{ $data['text'] ?? '' }}</{{ $headingLevel }}>
            </div>
            @break

        @case('text')
            @php
                $textFontFamily = $data['fontFamily'] ?? 'Inter';
                $textFontSize = $data['fontSize'] ?? 16;
                $textFontWeight = $data['fontWeight'] ?? 'normal';
                $textColor = $data['color'] ?? '#374151';
                $textLineHeight = $data['lineHeight'] ?? '1.75';
                $textLetterSpacing = $data['letterSpacing'] ?? 0;
                $textMaxWidth = $data['maxWidth'] ?? '';
                
                $weightMap = ['normal' => 400, 'medium' => 500, 'semibold' => 600, 'bold' => 700];
                $fontWeight = $weightMap[$textFontWeight] ?? 400;
                
                $textStyle = "font-family: '{$textFontFamily}', sans-serif; font-size: {$textFontSize}px; font-weight: {$fontWeight}; color: {$textColor}; line-height: {$textLineHeight}; letter-spacing: {$textLetterSpacing}px;";
                if ($textMaxWidth) $textStyle .= " max-width: {$textMaxWidth}px;";
            @endphp
            <div class="max-w-4xl mx-auto px-4" style="padding-top: {{ $spacingTop }}rem; padding-bottom: {{ $spacingBottom }}rem;">
                <div class="text-{{ $data['align'] ?? 'left' }} {{ $textMaxWidth ? 'mx-auto' : '' }}" style="{{ $textStyle }}">
                    {!! nl2br(e($data['content'] ?? '')) !!}
                </div>
            </div>
            @break

        @case('image')
            <div class="max-w-4xl mx-auto px-4 text-{{ $data['align'] ?? 'center' }}" style="padding-top: {{ $spacingTop }}rem; padding-bottom: {{ $spacingBottom }}rem;">
                @if(!empty($data['url']))
                    <img src="{{ $data['url'] }}" alt="{{ $data['alt'] ?? '' }}" 
                         class="inline-block rounded-lg shadow-lg" style="width: {{ $data['width'] ?? '100' }}%">
                    @if(!empty($data['caption']))
                        <p class="text-gray-500 text-sm mt-2">{{ $data['caption'] }}</p>
                    @endif
                @endif
            </div>
            @break

        @case('button')
            <div class="max-w-4xl mx-auto px-4 text-{{ $data['align'] ?? 'center' }}" style="padding-top: {{ $spacingTop }}rem; padding-bottom: {{ $spacingBottom }}rem;">
                @php
                    $btnClass = match($data['style'] ?? 'primary') {
                        'primary' => 'bg-sky-500 text-white hover:bg-sky-600',
                        'secondary' => 'bg-gray-600 text-white hover:bg-gray-700',
                        'outline' => 'border-2 border-sky-500 text-sky-500 hover:bg-sky-50',
                        default => 'bg-sky-500 text-white hover:bg-sky-600'
                    };
                @endphp
                <a href="{{ $data['url'] ?? '#' }}" class="inline-block px-6 py-3 font-semibold rounded-lg transition {{ $btnClass }}">
                    {{ $data['text'] ?? 'Button' }}
                </a>
            </div>
            @break

        @case('features')
            <section class="bg-gray-50 px-4" style="padding-top: {{ $spacingTop }}rem; padding-bottom: {{ $spacingBottom }}rem;">
                <div class="max-w-6xl mx-auto">
                    <div class="grid md:grid-cols-{{ $data['columns'] ?? 3 }} gap-6">
                        @foreach(($data['items'] ?? []) as $feature)
                            <div class="text-center p-6 bg-white rounded-xl shadow-sm">
                                @if(!empty($feature['icon']))
                                    <i class="{{ $feature['icon'] }} text-3xl text-sky-500 mb-4"></i>
                                @endif
                                <h3 class="text-xl font-semibold mb-2">{{ $feature['title'] ?? '' }}</h3>
                                <p class="text-gray-600">{{ $feature['description'] ?? '' }}</p>
                            </div>
                        @endforeach
                    </div>
                </div>
            </section>
            @break

        @case('cta')
            <section class="px-4" style="background-color: {{ $data['bgColor'] ?? '#1e293b' }}; padding-top: {{ $spacingTop }}rem; padding-bottom: {{ $spacingBottom }}rem;">
                <div class="max-w-4xl mx-auto text-center text-white">
                    <h2 class="text-3xl font-bold mb-4">{{ $data['title'] ?? '' }}</h2>
                    <p class="text-xl mb-8 opacity-90">{{ $data['subtitle'] ?? '' }}</p>
                    @if(!empty($data['buttonText']))
                        <a href="{{ $data['buttonUrl'] ?? '#' }}" class="inline-block px-8 py-3 bg-sky-500 text-white font-semibold rounded-lg hover:bg-sky-600 transition">
                            {{ $data['buttonText'] }}
                        </a>
                    @endif
                </div>
            </section>
            @break

        @case('spacer')
            <div style="height: {{ $data['height'] ?? 40 }}px"></div>
            @break

        @case('divider')
            <div class="max-w-4xl mx-auto px-4" style="padding-top: {{ $spacingTop }}rem; padding-bottom: {{ $spacingBottom }}rem;">
                <hr style="border-style: {{ $data['style'] ?? 'solid' }}; border-color: {{ $data['color'] ?? '#e5e7eb' }}">
            </div>
            @break

        @case('video')
            <div class="max-w-4xl mx-auto px-4" style="padding-top: {{ $spacingTop }}rem; padding-bottom: {{ $spacingBottom }}rem;">
                @if(!empty($data['url']))
                    @php
                        $videoId = '';
                        if (preg_match('/(?:youtube\.com\/watch\?v=|youtu\.be\/)([a-zA-Z0-9_-]+)/', $data['url'], $matches)) {
                            $videoId = $matches[1];
                        }
                    @endphp
                    @if($videoId)
                        <div class="relative pb-[56.25%] h-0 overflow-hidden rounded-lg shadow-lg">
                            <iframe src="https://www.youtube.com/embed/{{ $videoId }}" 
                                    class="absolute top-0 left-0 w-full h-full" 
                                    frameborder="0" allowfullscreen></iframe>
                        </div>
                    @endif
                @endif
            </div>
            @break

        @case('testimonial')
            <div class="max-w-3xl mx-auto px-4" style="padding-top: {{ $spacingTop }}rem; padding-bottom: {{ $spacingBottom }}rem;">
                <div class="bg-gray-50 rounded-2xl p-8 text-center">
                    <i class="fas fa-quote-left text-4xl text-sky-200 mb-4"></i>
                    <blockquote class="text-xl text-gray-700 mb-6">{{ $data['quote'] ?? '' }}</blockquote>
                    <div class="flex items-center justify-center space-x-4">
                        @if(!empty($data['avatar']))
                            <img src="{{ $data['avatar'] }}" alt="{{ $data['author'] ?? '' }}" class="w-12 h-12 rounded-full object-cover">
                        @else
                            <div class="w-12 h-12 rounded-full bg-sky-500 flex items-center justify-center text-white font-bold">
                                {{ strtoupper(substr($data['author'] ?? 'A', 0, 1)) }}
                            </div>
                        @endif
                        <div class="text-left">
                            <div class="font-semibold text-gray-900">{{ $data['author'] ?? '' }}</div>
                            <div class="text-sm text-gray-500">{{ $data['role'] ?? '' }}</div>
                        </div>
                    </div>
                </div>
            </div>
            @break

        @case('pricing')
            <section class="px-4" style="padding-top: {{ $spacingTop }}rem; padding-bottom: {{ $spacingBottom }}rem;">
                <div class="max-w-5xl mx-auto">
                    <div class="grid md:grid-cols-{{ count($data['plans'] ?? []) }} gap-6">
                        @foreach(($data['plans'] ?? []) as $plan)
                            <div class="bg-white rounded-2xl shadow-lg p-8 {{ !empty($plan['popular']) ? 'ring-2 ring-sky-500' : '' }}">
                                @if(!empty($plan['popular']))
                                    <span class="inline-block px-3 py-1 bg-sky-500 text-white text-xs font-semibold rounded-full mb-4">Popular</span>
                                @endif
                                <h3 class="text-2xl font-bold mb-2">{{ $plan['name'] ?? '' }}</h3>
                                <div class="text-4xl font-bold mb-6">
                                    ${{ $plan['price'] ?? '0' }}<span class="text-lg text-gray-500">/{{ $plan['period'] ?? 'month' }}</span>
                                </div>
                                <ul class="space-y-3 mb-8">
                                    @foreach(($plan['features'] ?? []) as $feature)
                                        <li class="flex items-center">
                                            <i class="fas fa-check text-green-500 mr-2"></i>
                                            {{ $feature }}
                                        </li>
                                    @endforeach
                                </ul>
                                <a href="{{ $plan['buttonUrl'] ?? '#' }}" class="block w-full text-center px-6 py-3 {{ !empty($plan['popular']) ? 'bg-sky-500 text-white hover:bg-sky-600' : 'bg-gray-100 text-gray-900 hover:bg-gray-200' }} font-semibold rounded-lg transition">
                                    {{ $plan['buttonText'] ?? 'Choose' }}
                                </a>
                            </div>
                        @endforeach
                    </div>
                </div>
            </section>
            @break

        @case('columns')
            <div class="max-w-6xl mx-auto px-4" style="padding-top: {{ $spacingTop }}rem; padding-bottom: {{ $spacingBottom }}rem;">
                <div class="grid md:grid-cols-2" style="gap: {{ $data['gap'] ?? 24 }}px;">
                    <!-- Left Column -->
                    <div>
                        @foreach(($data['left'] ?? []) as $item)
                            @if($item['type'] === 'heading')
                                <{{ $item['data']['level'] ?? 'h3' }} class="text-2xl font-bold text-gray-900 mb-3 text-{{ $item['data']['align'] ?? 'left' }}">
                                    {{ $item['data']['text'] ?? '' }}
                                </{{ $item['data']['level'] ?? 'h3' }}>
                            @elseif($item['type'] === 'text')
                                <p class="text-gray-600 mb-3 text-{{ $item['data']['align'] ?? 'left' }}">
                                    {!! nl2br(e($item['data']['content'] ?? '')) !!}
                                </p>
                            @elseif($item['type'] === 'image')
                                @if(!empty($item['data']['url']))
                                    <img src="{{ $item['data']['url'] }}" alt="{{ $item['data']['alt'] ?? '' }}" class="rounded-lg mb-3" style="width: {{ $item['data']['width'] ?? '100' }}%">
                                @endif
                            @elseif($item['type'] === 'button')
                                @php
                                    $btnStyle = $item['data']['style'] ?? 'primary';
                                    $btnSize = $item['data']['size'] ?? 'md';
                                    $btnFull = $item['data']['fullWidth'] ?? false;
                                    $btnClasses = match($btnStyle) {
                                        'primary' => 'bg-sky-500 text-white hover:bg-sky-600',
                                        'secondary' => 'bg-gray-600 text-white hover:bg-gray-700',
                                        'outline' => 'border-2 border-sky-500 text-sky-500 hover:bg-sky-50',
                                        'success' => 'bg-green-500 text-white hover:bg-green-600',
                                        'danger' => 'bg-red-500 text-white hover:bg-red-600',
                                        default => 'bg-sky-500 text-white hover:bg-sky-600'
                                    };
                                    $sizeClasses = match($btnSize) {
                                        'sm' => 'px-4 py-1.5 text-sm',
                                        'lg' => 'px-8 py-3 text-lg',
                                        default => 'px-6 py-2'
                                    };
                                @endphp
                                <a href="{{ $item['data']['url'] ?? '#' }}" class="{{ $btnFull ? 'block text-center' : 'inline-block' }} {{ $sizeClasses }} {{ $btnClasses }} font-semibold rounded-lg transition mb-3">
                                    {{ $item['data']['text'] ?? 'Button' }}
                                </a>
                            @elseif($item['type'] === 'list')
                                @php
                                    $listItems = explode("\n", $item['data']['items'] ?? '');
                                    $listStyle = $item['data']['style'] ?? 'bullet';
                                    $iconColor = $item['data']['iconColor'] ?? '#0ea5e9';
                                @endphp
                                <ul class="space-y-2 mb-3">
                                    @foreach($listItems as $idx => $listItem)
                                        @if(trim($listItem))
                                            <li class="flex items-start">
                                                @if($listStyle === 'number')
                                                    <span class="font-semibold mr-2" style="color: {{ $iconColor }}">{{ $idx + 1 }}.</span>
                                                @elseif($listStyle === 'check')
                                                    <i class="fas fa-check mr-2 mt-1" style="color: {{ $iconColor }}"></i>
                                                @elseif($listStyle === 'arrow')
                                                    <i class="fas fa-chevron-right mr-2 mt-1" style="color: {{ $iconColor }}"></i>
                                                @else
                                                    <i class="fas fa-circle text-xs mr-2 mt-1.5" style="color: {{ $iconColor }}"></i>
                                                @endif
                                                <span class="text-gray-700">{{ trim($listItem) }}</span>
                                            </li>
                                        @endif
                                    @endforeach
                                </ul>
                            @elseif($item['type'] === 'icon')
                                <div class="mb-4">
                                    <div class="flex items-start space-x-3">
                                        <i class="{{ $item['data']['icon'] ?? 'fas fa-star' }} text-2xl" style="color: {{ $item['data']['iconColor'] ?? '#0ea5e9' }}"></i>
                                        <div>
                                            <h4 class="font-semibold text-gray-900">{{ $item['data']['title'] ?? '' }}</h4>
                                            <p class="text-gray-600 text-sm">{{ $item['data']['description'] ?? '' }}</p>
                                        </div>
                                    </div>
                                </div>
                            @elseif($item['type'] === 'video')
                                @php
                                    $videoUrl = $item['data']['url'] ?? '';
                                    $aspectRatio = $item['data']['aspectRatio'] ?? '16:9';
                                    $paddingBottom = match($aspectRatio) {
                                        '4:3' => '75%',
                                        '1:1' => '100%',
                                        default => '56.25%'
                                    };
                                    preg_match('/(?:youtube\.com\/watch\?v=|youtu\.be\/|vimeo\.com\/)([a-zA-Z0-9_-]+)/', $videoUrl, $matches);
                                    $videoId = $matches[1] ?? '';
                                    $isYoutube = str_contains($videoUrl, 'youtube') || str_contains($videoUrl, 'youtu.be');
                                @endphp
                                @if($videoId)
                                    <div class="relative mb-3" style="padding-bottom: {{ $paddingBottom }}">
                                        <iframe class="absolute inset-0 w-full h-full rounded-lg" 
                                            src="{{ $isYoutube ? 'https://www.youtube.com/embed/' . $videoId : 'https://player.vimeo.com/video/' . $videoId }}" 
                                            frameborder="0" allowfullscreen></iframe>
                                    </div>
                                @endif
                            @elseif($item['type'] === 'spacer')
                                <div style="height: {{ $item['data']['height'] ?? 20 }}px; background-color: {{ $item['data']['bgColor'] ?? 'transparent' }};"></div>
                            @elseif($item['type'] === 'divider')
                                <hr class="border-gray-300 my-3">
                            @endif
                        @endforeach
                    </div>
                    <!-- Right Column -->
                    <div>
                        @foreach(($data['right'] ?? []) as $item)
                            @if($item['type'] === 'heading')
                                <{{ $item['data']['level'] ?? 'h3' }} class="text-2xl font-bold text-gray-900 mb-3 text-{{ $item['data']['align'] ?? 'left' }}">
                                    {{ $item['data']['text'] ?? '' }}
                                </{{ $item['data']['level'] ?? 'h3' }}>
                            @elseif($item['type'] === 'text')
                                <p class="text-gray-600 mb-3 text-{{ $item['data']['align'] ?? 'left' }}">
                                    {!! nl2br(e($item['data']['content'] ?? '')) !!}
                                </p>
                            @elseif($item['type'] === 'image')
                                @if(!empty($item['data']['url']))
                                    <img src="{{ $item['data']['url'] }}" alt="{{ $item['data']['alt'] ?? '' }}" class="rounded-lg mb-3" style="width: {{ $item['data']['width'] ?? '100' }}%">
                                @endif
                            @elseif($item['type'] === 'button')
                                @php
                                    $btnStyle = $item['data']['style'] ?? 'primary';
                                    $btnSize = $item['data']['size'] ?? 'md';
                                    $btnFull = $item['data']['fullWidth'] ?? false;
                                    $btnClasses = match($btnStyle) {
                                        'primary' => 'bg-sky-500 text-white hover:bg-sky-600',
                                        'secondary' => 'bg-gray-600 text-white hover:bg-gray-700',
                                        'outline' => 'border-2 border-sky-500 text-sky-500 hover:bg-sky-50',
                                        'success' => 'bg-green-500 text-white hover:bg-green-600',
                                        'danger' => 'bg-red-500 text-white hover:bg-red-600',
                                        default => 'bg-sky-500 text-white hover:bg-sky-600'
                                    };
                                    $sizeClasses = match($btnSize) {
                                        'sm' => 'px-4 py-1.5 text-sm',
                                        'lg' => 'px-8 py-3 text-lg',
                                        default => 'px-6 py-2'
                                    };
                                @endphp
                                <a href="{{ $item['data']['url'] ?? '#' }}" class="{{ $btnFull ? 'block text-center' : 'inline-block' }} {{ $sizeClasses }} {{ $btnClasses }} font-semibold rounded-lg transition mb-3">
                                    {{ $item['data']['text'] ?? 'Button' }}
                                </a>
                            @elseif($item['type'] === 'list')
                                @php
                                    $listItems = explode("\n", $item['data']['items'] ?? '');
                                    $listStyle = $item['data']['style'] ?? 'bullet';
                                    $iconColor = $item['data']['iconColor'] ?? '#0ea5e9';
                                @endphp
                                <ul class="space-y-2 mb-3">
                                    @foreach($listItems as $idx => $listItem)
                                        @if(trim($listItem))
                                            <li class="flex items-start">
                                                @if($listStyle === 'number')
                                                    <span class="font-semibold mr-2" style="color: {{ $iconColor }}">{{ $idx + 1 }}.</span>
                                                @elseif($listStyle === 'check')
                                                    <i class="fas fa-check mr-2 mt-1" style="color: {{ $iconColor }}"></i>
                                                @elseif($listStyle === 'arrow')
                                                    <i class="fas fa-chevron-right mr-2 mt-1" style="color: {{ $iconColor }}"></i>
                                                @else
                                                    <i class="fas fa-circle text-xs mr-2 mt-1.5" style="color: {{ $iconColor }}"></i>
                                                @endif
                                                <span class="text-gray-700">{{ trim($listItem) }}</span>
                                            </li>
                                        @endif
                                    @endforeach
                                </ul>
                            @elseif($item['type'] === 'icon')
                                <div class="mb-4">
                                    <div class="flex items-start space-x-3">
                                        <i class="{{ $item['data']['icon'] ?? 'fas fa-star' }} text-2xl" style="color: {{ $item['data']['iconColor'] ?? '#0ea5e9' }}"></i>
                                        <div>
                                            <h4 class="font-semibold text-gray-900">{{ $item['data']['title'] ?? '' }}</h4>
                                            <p class="text-gray-600 text-sm">{{ $item['data']['description'] ?? '' }}</p>
                                        </div>
                                    </div>
                                </div>
                            @elseif($item['type'] === 'video')
                                @php
                                    $videoUrl = $item['data']['url'] ?? '';
                                    $aspectRatio = $item['data']['aspectRatio'] ?? '16:9';
                                    $paddingBottom = match($aspectRatio) {
                                        '4:3' => '75%',
                                        '1:1' => '100%',
                                        default => '56.25%'
                                    };
                                    preg_match('/(?:youtube\.com\/watch\?v=|youtu\.be\/|vimeo\.com\/)([a-zA-Z0-9_-]+)/', $videoUrl, $matches);
                                    $videoId = $matches[1] ?? '';
                                    $isYoutube = str_contains($videoUrl, 'youtube') || str_contains($videoUrl, 'youtu.be');
                                @endphp
                                @if($videoId)
                                    <div class="relative mb-3" style="padding-bottom: {{ $paddingBottom }}">
                                        <iframe class="absolute inset-0 w-full h-full rounded-lg" 
                                            src="{{ $isYoutube ? 'https://www.youtube.com/embed/' . $videoId : 'https://player.vimeo.com/video/' . $videoId }}" 
                                            frameborder="0" allowfullscreen></iframe>
                                    </div>
                                @endif
                            @elseif($item['type'] === 'spacer')
                                <div style="height: {{ $item['data']['height'] ?? 20 }}px; background-color: {{ $item['data']['bgColor'] ?? 'transparent' }};"></div>
                            @elseif($item['type'] === 'divider')
                                <hr class="border-gray-300 my-3">
                            @endif
                        @endforeach
                    </div>
                </div>
            </div>
            @break
    @endswitch
@endforeach
