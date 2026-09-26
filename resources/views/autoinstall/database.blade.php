@extends('autoinstall.layout')

@section('content')
    <div>
        <h2 class="text-2xl font-bold text-white mb-2">Database Configuration</h2>
        <p class="text-purple-200 mb-6">Enter your MySQL database credentials below.</p>

        <form action="{{ route('autoinstall.setup-database') }}" method="POST" class="space-y-5">
            @csrf

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-purple-200 text-sm font-medium mb-2" for="db_host">
                        <i class="fas fa-server mr-1"></i> Database Host
                    </label>
                    <input type="text" name="db_host" id="db_host" value="{{ old('db_host', 'localhost') }}"
                        class="w-full px-4 py-3 bg-white/10 border border-white/20 rounded-lg text-white placeholder-purple-300/50 focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                        placeholder="localhost">
                    @error('db_host')
                        <p class="text-red-400 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-purple-200 text-sm font-medium mb-2" for="db_port">
                        <i class="fas fa-plug mr-1"></i> Port
                    </label>
                    <input type="text" name="db_port" id="db_port" value="{{ old('db_port', '3306') }}"
                        class="w-full px-4 py-3 bg-white/10 border border-white/20 rounded-lg text-white placeholder-purple-300/50 focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                        placeholder="3306">
                    @error('db_port')
                        <p class="text-red-400 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div>
                <label class="block text-purple-200 text-sm font-medium mb-2" for="db_database">
                    <i class="fas fa-database mr-1"></i> Database Name
                </label>
                <input type="text" name="db_database" id="db_database" value="{{ old('db_database') }}"
                    class="w-full px-4 py-3 bg-white/10 border border-white/20 rounded-lg text-white placeholder-purple-300/50 focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                    placeholder="your_database_name">
                @error('db_database')
                    <p class="text-red-400 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="block text-purple-200 text-sm font-medium mb-2" for="db_username">
                    <i class="fas fa-user mr-1"></i> Database Username
                </label>
                <input type="text" name="db_username" id="db_username" value="{{ old('db_username') }}"
                    class="w-full px-4 py-3 bg-white/10 border border-white/20 rounded-lg text-white placeholder-purple-300/50 focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                    placeholder="database_user">
                @error('db_username')
                    <p class="text-red-400 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="block text-purple-200 text-sm font-medium mb-2" for="db_password">
                    <i class="fas fa-lock mr-1"></i> Database Password
                </label>
                <input type="password" name="db_password" id="db_password" value="{{ old('db_password') }}"
                    class="w-full px-4 py-3 bg-white/10 border border-white/20 rounded-lg text-white placeholder-purple-300/50 focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                    placeholder="••••••••">
                @error('db_password')
                    <p class="text-red-400 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div id="connection-result" class="hidden"></div>

            <div class="mt-8 flex justify-between items-center">
                <a href="{{ route('autoinstall.requirements') }}"
                    class="inline-flex items-center px-6 py-2 text-purple-300 hover:text-white transition-colors">
                    <i class="fas fa-arrow-left mr-2"></i>
                    Back
                </a>

                <div class="flex items-center space-x-4">
                    <button type="button" onclick="testConnection()"
                        class="inline-flex items-center px-6 py-3 bg-white/10 border border-white/20 text-white rounded-lg hover:bg-white/20 transition-all">
                        <i class="fas fa-plug mr-2" id="test-icon"></i>
                        <span id="test-text">Test Connection</span>
                    </button>

                    <button type="submit"
                        class="inline-flex items-center px-8 py-3 bg-gradient-to-r from-purple-500 to-pink-500 text-white font-semibold rounded-lg shadow-lg hover:from-purple-600 hover:to-pink-600 transition-all duration-300">
                        Continue
                        <i class="fas fa-arrow-right ml-2"></i>
                    </button>
                </div>
            </div>
        </form>
    </div>
@endsection

@push('scripts')
    <script>
        function testConnection() {
            const testBtn = document.querySelector('button[onclick="testConnection()"]');
            const testIcon = document.getElementById('test-icon');
            const testText = document.getElementById('test-text');
            const resultDiv = document.getElementById('connection-result');

            const originalIcon = testIcon.className;
            testIcon.className = 'fas fa-spinner fa-spin mr-2';
            testText.textContent = 'Testing...';
            testBtn.disabled = true;

            const formData = new FormData();
            formData.append('db_host', document.getElementById('db_host').value);
            formData.append('db_port', document.getElementById('db_port').value);
            formData.append('db_database', document.getElementById('db_database').value);
            formData.append('db_username', document.getElementById('db_username').value);
            formData.append('db_password', document.getElementById('db_password').value);
            formData.append('_token', '{{ csrf_token() }}');

            fetch('{{ route("autoinstall.test-connection") }}', {
                method: 'POST',
                body: formData
            })
                .then(response => response.json())
                .then(data => {
                    resultDiv.classList.remove('hidden');
                    if (data.success) {
                        resultDiv.className = 'bg-emerald-500/20 border border-emerald-500/30 text-emerald-200 px-4 py-3 rounded-lg';
                        resultDiv.innerHTML = '<i class="fas fa-check-circle mr-2"></i>' + data.message;
                    } else {
                        resultDiv.className = 'bg-red-500/20 border border-red-500/30 text-red-200 px-4 py-3 rounded-lg';
                        resultDiv.innerHTML = '<i class="fas fa-exclamation-circle mr-2"></i>' + data.message;
                    }
                })
                .catch(error => {
                    resultDiv.classList.remove('hidden');
                    resultDiv.className = 'bg-red-500/20 border border-red-500/30 text-red-200 px-4 py-3 rounded-lg';
                    resultDiv.innerHTML = '<i class="fas fa-exclamation-circle mr-2"></i>Connection test failed: ' + error.message;
                })
                .finally(() => {
                    testIcon.className = originalIcon;
                    testText.textContent = 'Test Connection';
                    testBtn.disabled = false;
                });
        }
    </script>
@endpush