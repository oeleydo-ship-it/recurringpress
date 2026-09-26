@extends('superadmin.layout')

@section('title', 'System Updates')

@section('content')
    <div class="max-w-4xl">
        <div class="mb-6">
            <p class="text-gray-600">Manage system updates and backups.</p>
        </div>

        @if(session('success'))
            <div class="mb-6 bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg">
                <i class="fas fa-check-circle mr-2"></i>{{ session('success') }}
            </div>
        @endif

        @if(session('error'))
            <div class="mb-6 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg">
                <i class="fas fa-exclamation-circle mr-2"></i>{{ session('error') }}
            </div>
        @endif

        <div class="grid gap-6">
            <!-- Current Version -->
            <div class="bg-white rounded-xl shadow-sm p-6">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-lg font-semibold text-gray-900">Current Version</h2>
                    <span class="px-3 py-1 bg-sky-100 text-sky-700 rounded-full text-sm font-medium">
                        v{{ $currentVersion }}
                    </span>
                </div>

                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-600 text-sm">
                            @if($lastUpdateCheck)
                                Last checked: {{ \Carbon\Carbon::parse($lastUpdateCheck)->diffForHumans() }}
                            @else
                                Never checked for updates
                            @endif
                        </p>
                    </div>
                    <button type="button" id="checkUpdatesBtn" onclick="checkForUpdates()"
                        class="px-4 py-2 bg-sky-500 text-white rounded-lg hover:bg-sky-600 transition">
                        <i class="fas fa-sync-alt mr-2"></i>Check for Updates
                    </button>
                </div>

                <div id="updateResult" class="mt-4 hidden"></div>
            </div>

            <!-- Update Settings -->
            <div class="bg-white rounded-xl shadow-sm p-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">Update Settings</h2>

                <form action="{{ route('superadmin.updates.settings') }}" method="POST" class="space-y-4">
                    @csrf
                    @method('PUT')

                    <div>
                        <label class="flex items-center">
                            <input type="checkbox" name="auto_update_enabled" value="1" {{ $autoUpdateEnabled ? 'checked' : '' }}
                                class="w-4 h-4 text-sky-600 border-gray-300 rounded focus:ring-sky-500">
                            <span class="ml-2 text-sm text-gray-700">Enable automatic update checks</span>
                        </label>
                        <p class="text-xs text-gray-500 mt-1 ml-6">System will check for updates daily</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Update Method</label>
                        <select name="update_method"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-sky-500 focus:border-sky-500">
                            <option value="manual" {{ $updateMethod === 'manual' ? 'selected' : '' }}>Manual (Download & Upload)</option>
                            <option value="server" {{ $updateMethod === 'server' ? 'selected' : '' }}>From Update Server</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Update Server URL</label>
                        <input type="url" name="update_server_url" value="{{ $updateServerUrl }}"
                            placeholder="https://updates.yoursite.com"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-sky-500 focus:border-sky-500">
                        <p class="text-xs text-gray-500 mt-1">The URL of your update server (required for automatic updates from server)</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">License Key</label>
                        <input type="text" name="license_key" value="{{ $licenseKey }}"
                            placeholder="Enter your license key for automatic updates"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-sky-500 focus:border-sky-500">
                        <p class="text-xs text-gray-500 mt-1">Required for downloading updates from the server</p>
                    </div>

                    <div class="pt-4">
                        <button type="submit" class="px-4 py-2 bg-sky-500 text-white rounded-lg hover:bg-sky-600 transition">
                            <i class="fas fa-save mr-2"></i>Save Settings
                        </button>
                    </div>
                </form>
            </div>

            <!-- Manual Upload -->
            <div class="bg-white rounded-xl shadow-sm p-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">Manual Update</h2>
                <p class="text-gray-600 text-sm mb-4">
                    Upload an update package (.zip) to update the system manually.
                </p>

                <form action="{{ route('superadmin.updates.upload') }}" method="POST" enctype="multipart/form-data" class="space-y-4">
                    @csrf

                    <div class="grid md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Update Package (.zip) *</label>
                            <input type="file" name="update_file" accept=".zip" required
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-sky-500 focus:border-sky-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Version Number *</label>
                            <input type="text" name="version" placeholder="e.g., 1.1.0" required
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-sky-500 focus:border-sky-500">
                        </div>
                    </div>

                    <div class="bg-amber-50 border border-amber-200 rounded-lg p-4">
                        <div class="flex items-start">
                            <i class="fas fa-exclamation-triangle text-amber-500 mt-0.5 mr-3"></i>
                            <div>
                                <h4 class="font-semibold text-amber-800">Important</h4>
                                <p class="text-amber-700 text-sm mt-1">
                                    A backup will be created automatically before applying the update. Make sure you have sufficient storage space.
                                </p>
                            </div>
                        </div>
                    </div>

                    <button type="submit" class="px-4 py-2 bg-green-500 text-white rounded-lg hover:bg-green-600 transition">
                        <i class="fas fa-upload mr-2"></i>Upload & Install Update
                    </button>
                </form>
            </div>

            <!-- Backups -->
            <div class="bg-white rounded-xl shadow-sm p-6">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-lg font-semibold text-gray-900">System Backups</h2>
                    <button type="button" onclick="loadBackups()" class="text-sky-500 hover:text-sky-700">
                        <i class="fas fa-sync-alt"></i>
                    </button>
                </div>

                <div id="backupsList">
                    <p class="text-gray-500 text-sm">Loading backups...</p>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            loadBackups();
        });

        function checkForUpdates() {
            const btn = document.getElementById('checkUpdatesBtn');
            const resultDiv = document.getElementById('updateResult');
            
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Checking...';
            resultDiv.classList.add('hidden');

            fetch('{{ route("superadmin.updates.check") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                }
            })
            .then(response => response.json())
            .then(data => {
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-sync-alt mr-2"></i>Check for Updates';
                resultDiv.classList.remove('hidden');

                if (data.success) {
                    if (data.update_available) {
                        resultDiv.innerHTML = `
                            <div class="bg-green-50 border border-green-200 rounded-lg p-4">
                                <div class="flex items-start">
                                    <i class="fas fa-gift text-green-500 mt-0.5 mr-3"></i>
                                    <div class="flex-1">
                                        <h4 class="font-semibold text-green-800">Update Available!</h4>
                                        <p class="text-green-700 text-sm mt-1">Version ${data.latest_version} is available.</p>
                                        ${data.changelog ? `<p class="text-green-600 text-sm mt-2">${data.changelog}</p>` : ''}
                                        <button onclick="downloadUpdate('${data.download_url}', '${data.latest_version}')" 
                                            class="mt-3 px-4 py-2 bg-green-500 text-white rounded-lg hover:bg-green-600 transition text-sm">
                                            <i class="fas fa-download mr-2"></i>Download & Install
                                        </button>
                                    </div>
                                </div>
                            </div>
                        `;
                    } else {
                        resultDiv.innerHTML = `
                            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                                <div class="flex items-center">
                                    <i class="fas fa-check-circle text-blue-500 mr-3"></i>
                                    <span class="text-blue-700">You are running the latest version.</span>
                                </div>
                            </div>
                        `;
                    }
                } else {
                    resultDiv.innerHTML = `
                        <div class="bg-red-50 border border-red-200 rounded-lg p-4">
                            <div class="flex items-center">
                                <i class="fas fa-exclamation-circle text-red-500 mr-3"></i>
                                <span class="text-red-700">${data.message}</span>
                            </div>
                        </div>
                    `;
                }
            })
            .catch(error => {
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-sync-alt mr-2"></i>Check for Updates';
                resultDiv.classList.remove('hidden');
                resultDiv.innerHTML = `
                    <div class="bg-red-50 border border-red-200 rounded-lg p-4">
                        <div class="flex items-center">
                            <i class="fas fa-exclamation-circle text-red-500 mr-3"></i>
                            <span class="text-red-700">Could not check for updates. Please try again.</span>
                        </div>
                    </div>
                `;
            });
        }

        function downloadUpdate(url, version) {
            if (!confirm('Are you sure you want to download and install version ' + version + '? A backup will be created first.')) {
                return;
            }

            const resultDiv = document.getElementById('updateResult');
            resultDiv.innerHTML = `
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                    <div class="flex items-center">
                        <i class="fas fa-spinner fa-spin text-blue-500 mr-3"></i>
                        <span class="text-blue-700">Downloading and installing update... Please wait.</span>
                    </div>
                </div>
            `;

            fetch('{{ route("superadmin.updates.download") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({ download_url: url, version: version })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    resultDiv.innerHTML = `
                        <div class="bg-green-50 border border-green-200 rounded-lg p-4">
                            <div class="flex items-center">
                                <i class="fas fa-check-circle text-green-500 mr-3"></i>
                                <span class="text-green-700">Update installed successfully! Reloading page...</span>
                            </div>
                        </div>
                    `;
                    setTimeout(() => location.reload(), 2000);
                } else {
                    resultDiv.innerHTML = `
                        <div class="bg-red-50 border border-red-200 rounded-lg p-4">
                            <div class="flex items-center">
                                <i class="fas fa-exclamation-circle text-red-500 mr-3"></i>
                                <span class="text-red-700">${data.message}</span>
                            </div>
                        </div>
                    `;
                }
            })
            .catch(error => {
                resultDiv.innerHTML = `
                    <div class="bg-red-50 border border-red-200 rounded-lg p-4">
                        <div class="flex items-center">
                            <i class="fas fa-exclamation-circle text-red-500 mr-3"></i>
                            <span class="text-red-700">Update failed. Please try again or use manual upload.</span>
                        </div>
                    </div>
                `;
            });
        }

        function loadBackups() {
            fetch('{{ route("superadmin.updates.backups") }}')
                .then(response => response.json())
                .then(data => {
                    const container = document.getElementById('backupsList');
                    
                    if (data.backups.length === 0) {
                        container.innerHTML = '<p class="text-gray-500 text-sm">No backups found.</p>';
                        return;
                    }

                    let html = '<div class="space-y-2">';
                    data.backups.forEach(backup => {
                        html += `
                            <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                                <div>
                                    <p class="font-medium text-gray-900">${backup.name}</p>
                                    <p class="text-sm text-gray-500">${backup.date} • ${backup.size}</p>
                                </div>
                                <div class="flex items-center gap-2">
                                    <form action="{{ route('superadmin.updates.restore') }}" method="POST" class="inline" 
                                        onsubmit="return confirm('Are you sure you want to restore this backup? Current files will be overwritten.');">
                                        @csrf
                                        <input type="hidden" name="backup_file" value="${backup.name}">
                                        <button type="submit" class="px-3 py-1 text-sky-600 hover:bg-sky-50 rounded transition">
                                            <i class="fas fa-undo"></i>
                                        </button>
                                    </form>
                                    <form action="{{ route('superadmin.updates.backup.delete') }}" method="POST" class="inline"
                                        onsubmit="return confirm('Delete this backup?');">
                                        @csrf
                                        @method('DELETE')
                                        <input type="hidden" name="backup_file" value="${backup.name}">
                                        <button type="submit" class="px-3 py-1 text-red-600 hover:bg-red-50 rounded transition">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        `;
                    });
                    html += '</div>';
                    container.innerHTML = html;
                })
                .catch(error => {
                    document.getElementById('backupsList').innerHTML = 
                        '<p class="text-red-500 text-sm">Could not load backups.</p>';
                });
        }
    </script>
    @endpush
@endsection
