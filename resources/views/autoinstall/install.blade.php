@extends('autoinstall.layout')

@section('content')
    <div>
        <h2 class="text-2xl font-bold text-white mb-2">Installing {{ $appName }}</h2>
        <p class="text-purple-200 mb-6">Please wait while we set up your application. This may take a few minutes.</p>

        <div class="space-y-4" id="installation-steps">
            <div class="bg-white/5 rounded-lg p-4">
                <div class="flex items-center justify-between mb-3">
                    <span class="text-purple-200 font-medium">Installation Progress</span>
                    <span class="text-white font-bold" id="progress-percent">0%</span>
                </div>
                <div class="w-full bg-white/20 rounded-full h-3 overflow-hidden">
                    <div class="progress-bar h-3 rounded-full transition-all duration-500" id="progress-bar"
                        style="width: 0%"></div>
                </div>
            </div>

            <div class="space-y-2" id="step-log">
                {{-- Steps will be added here dynamically --}}
            </div>
        </div>

        <div class="mt-8 text-center hidden" id="install-complete">
            <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-emerald-500 mb-4">
                <i class="fas fa-check text-3xl text-white"></i>
            </div>
            <h3 class="text-xl font-bold text-white mb-2">Installation Complete!</h3>
            <p class="text-purple-200 mb-6">Your application has been successfully installed.</p>
            <a href="{{ route('autoinstall.complete') }}"
                class="inline-flex items-center px-8 py-3 bg-gradient-to-r from-emerald-500 to-cyan-500 text-white font-semibold rounded-lg shadow-lg hover:from-emerald-600 hover:to-cyan-600 transition-all duration-300">
                <i class="fas fa-arrow-right mr-2"></i>
                Continue
            </a>
        </div>

        <div class="mt-8 text-center hidden" id="install-error">
            <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-red-500 mb-4">
                <i class="fas fa-times text-3xl text-white"></i>
            </div>
            <h3 class="text-xl font-bold text-white mb-2">Installation Failed</h3>
            <p class="text-red-300 mb-6" id="error-message"></p>
            <a href="{{ route('autoinstall.database') }}"
                class="inline-flex items-center px-6 py-2 text-purple-300 hover:text-white transition-colors">
                <i class="fas fa-arrow-left mr-2"></i>
                Go Back and Try Again
            </a>
        </div>

        <div class="mt-8 text-center" id="install-button-container">
            <button type="button" onclick="startInstallation()" id="install-button"
                class="inline-flex items-center px-8 py-3 bg-gradient-to-r from-purple-500 to-pink-500 text-white font-semibold rounded-lg shadow-lg hover:from-purple-600 hover:to-pink-600 transition-all duration-300 transform hover:scale-105">
                <i class="fas fa-rocket mr-2"></i>
                Start Installation
            </button>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        let installInProgress = false;

        function addStep(step, status, message = '') {
            const stepLog = document.getElementById('step-log');
            const stepDiv = document.createElement('div');
            stepDiv.className = 'flex items-center justify-between bg-white/5 rounded-lg px-4 py-3';

            let icon, iconClass;
            switch (status) {
                case 'success':
                    icon = 'check-circle';
                    iconClass = 'text-emerald-400';
                    break;
                case 'warning':
                    icon = 'exclamation-triangle';
                    iconClass = 'text-yellow-400';
                    break;
                case 'error':
                    icon = 'times-circle';
                    iconClass = 'text-red-400';
                    break;
                default:
                    icon = 'spinner fa-spin';
                    iconClass = 'text-purple-400';
            }

            stepDiv.innerHTML = `
            <span class="text-purple-200">${step}</span>
            <div class="flex items-center">
                ${message ? `<span class="text-xs text-purple-300/60 mr-2">${message}</span>` : ''}
                <i class="fas fa-${icon} ${iconClass}"></i>
            </div>
        `;

            stepLog.appendChild(stepDiv);
            stepLog.scrollTop = stepLog.scrollHeight;
        }

        function updateProgress(percent) {
            document.getElementById('progress-bar').style.width = percent + '%';
            document.getElementById('progress-percent').textContent = percent + '%';
        }

        function startInstallation() {
            if (installInProgress) return;
            installInProgress = true;

            const installBtn = document.getElementById('install-button');
            const btnContainer = document.getElementById('install-button-container');

            installBtn.disabled = true;
            installBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Installing...';

            addStep('Starting installation...', 'pending');
            updateProgress(5);

            fetch('{{ route("autoinstall.run-install") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                }
            })
                .then(response => response.json())
                .then(data => {
                    // Clear pending step
                    document.getElementById('step-log').innerHTML = '';

                    if (data.steps) {
                        data.steps.forEach((step, index) => {
                            setTimeout(() => {
                                addStep(step.step, step.status, step.message || '');
                                updateProgress(Math.min(95, Math.round((index + 1) / data.steps.length * 100)));
                            }, index * 200);
                        });
                    }

                    setTimeout(() => {
                        if (data.success) {
                            updateProgress(100);
                            btnContainer.classList.add('hidden');
                            document.getElementById('install-complete').classList.remove('hidden');
                        } else {
                            document.getElementById('install-error').classList.remove('hidden');
                            document.getElementById('error-message').textContent = data.message || 'An unexpected error occurred.';
                            btnContainer.classList.add('hidden');
                        }
                    }, data.steps ? data.steps.length * 200 + 500 : 500);
                })
                .catch(error => {
                    document.getElementById('install-error').classList.remove('hidden');
                    document.getElementById('error-message').textContent = error.message || 'An unexpected error occurred.';
                    btnContainer.classList.add('hidden');
                });
        }
    </script>
@endpush