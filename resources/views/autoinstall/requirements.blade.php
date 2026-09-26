@extends('autoinstall.layout')

@section('content')
    <div>
        <h2 class="text-2xl font-bold text-white mb-2">Server Requirements</h2>
        <p class="text-purple-200 mb-6">Please ensure your server meets these requirements before proceeding.</p>

        <div class="space-y-6">
            {{-- PHP Requirements --}}
            <div>
                <h3 class="text-lg font-semibold text-white mb-3">
                    <i class="fas fa-php text-purple-400 mr-2"></i>
                    PHP Requirements
                </h3>
                <div class="space-y-2">
                    @foreach($requirements as $key => $req)
                        <div class="flex items-center justify-between bg-white/5 rounded-lg px-4 py-3">
                            <span class="text-purple-200">{{ $req['name'] }}</span>
                            <div class="flex items-center">
                                <span class="text-sm text-purple-300 mr-3">{{ $req['current'] }}</span>
                                @if($req['passed'])
                                    <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-emerald-500">
                                        <i class="fas fa-check text-white text-xs"></i>
                                    </span>
                                @else
                                    <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-red-500">
                                        <i class="fas fa-times text-white text-xs"></i>
                                    </span>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- Folder Permissions --}}
            <div>
                <h3 class="text-lg font-semibold text-white mb-3">
                    <i class="fas fa-folder-open text-purple-400 mr-2"></i>
                    Folder Permissions
                </h3>
                <div class="space-y-2">
                    @foreach($permissions as $key => $perm)
                        <div class="flex items-center justify-between bg-white/5 rounded-lg px-4 py-3">
                            <span class="text-purple-200 font-mono text-sm">{{ $perm['path'] }}</span>
                            <div class="flex items-center">
                                <span class="text-sm text-purple-300 mr-3">{{ $perm['current'] }}</span>
                                @if($perm['passed'])
                                    <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-emerald-500">
                                        <i class="fas fa-check text-white text-xs"></i>
                                    </span>
                                @else
                                    <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-red-500">
                                        <i class="fas fa-times text-white text-xs"></i>
                                    </span>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="mt-8 flex justify-between items-center">
            <a href="{{ route('autoinstall.detect') }}"
                class="inline-flex items-center px-6 py-2 text-purple-300 hover:text-white transition-colors">
                <i class="fas fa-arrow-left mr-2"></i>
                Back
            </a>

            @if($allPassed)
                <a href="{{ route('autoinstall.database') }}"
                    class="inline-flex items-center px-8 py-3 bg-gradient-to-r from-purple-500 to-pink-500 text-white font-semibold rounded-lg shadow-lg hover:from-purple-600 hover:to-pink-600 transition-all duration-300">
                    Continue
                    <i class="fas fa-arrow-right ml-2"></i>
                </a>
            @else
                <div class="bg-red-500/20 border border-red-500/30 text-red-200 px-4 py-2 rounded-lg text-sm">
                    <i class="fas fa-exclamation-triangle mr-2"></i>
                    Please fix the requirements above before continuing
                </div>
            @endif
        </div>
    </div>
@endsection