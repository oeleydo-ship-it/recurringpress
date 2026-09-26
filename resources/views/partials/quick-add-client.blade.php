<div x-data="quickAddClient()" class="inline-block">
    <button type="button" @click="open = true" class="text-xs font-medium text-primary text-primary-hover">
        <i class="fas fa-plus mr-1"></i>New Client
    </button>

    <div x-show="open" x-cloak class="fixed inset-0 z-[60]" style="display: none;">
        <div class="fixed inset-0 bg-gray-900/60" @click="close()"></div>
        <div class="fixed inset-0 overflow-y-auto">
            <div class="flex min-h-full items-center justify-center p-4">
                <div x-show="open" x-transition @click.stop
                     class="relative bg-white rounded-xl shadow-xl w-full max-w-md p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-semibold text-gray-900">New Client</h3>
                        <button type="button" @click="close()" class="text-gray-400 hover:text-gray-600">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>

                    <div class="space-y-4">
                        <p x-show="generalError" x-text="generalError" x-cloak class="text-sm p-3 bg-red-50 border border-red-200 text-red-700 rounded-lg"></p>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Client Type *</label>
                            <div class="flex gap-3">
                                <label class="flex-1 flex items-center justify-center gap-2 px-3 py-1.5 border rounded-lg cursor-pointer transition text-sm"
                                       :class="clientType === 'individual' ? 'border-primary bg-primary-light text-primary-dark' : 'border-gray-300 text-gray-600 hover:bg-gray-50'">
                                    <input type="radio" value="individual" x-model="clientType" class="sr-only">
                                    <i class="fas fa-user"></i> Individual
                                </label>
                                <label class="flex-1 flex items-center justify-center gap-2 px-3 py-1.5 border rounded-lg cursor-pointer transition text-sm"
                                       :class="clientType === 'company' ? 'border-primary bg-primary-light text-primary-dark' : 'border-gray-300 text-gray-600 hover:bg-gray-50'">
                                    <input type="radio" value="company" x-model="clientType" class="sr-only">
                                    <i class="fas fa-building"></i> Company
                                </label>
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                <span x-show="clientType === 'company'" x-cloak>Contact Person *</span>
                                <span x-show="clientType !== 'company'">Client Name *</span>
                            </label>
                            <input type="text" x-model="name" @keydown.enter.prevent="save()"
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary">
                            <p x-show="errors.name" x-text="errors.name && errors.name[0]" x-cloak class="text-sm text-red-500 mt-1"></p>
                        </div>

                        <div x-show="clientType === 'company'" x-cloak>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Company Name *</label>
                            <input type="text" x-model="companyName" @keydown.enter.prevent="save()"
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary">
                            <p x-show="errors.company_name" x-text="errors.company_name && errors.company_name[0]" x-cloak class="text-sm text-red-500 mt-1"></p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                            <input type="email" x-model="email" @keydown.enter.prevent="save()"
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary">
                            <p x-show="errors.email" x-text="errors.email && errors.email[0]" x-cloak class="text-sm text-red-500 mt-1"></p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Phone</label>
                            <input type="text" x-model="phone" @keydown.enter.prevent="save()"
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary">
                        </div>
                    </div>

                    <div class="flex justify-end gap-3 mt-6">
                        <button type="button" @click="close()" class="px-4 py-2 text-gray-700 hover:text-gray-900">Cancel</button>
                        <button type="button" @click="save()" :disabled="saving"
                                class="px-4 py-2 bg-primary bg-primary-hover text-white rounded-lg font-medium disabled:opacity-50">
                            <span x-show="!saving">Save Client</span>
                            <span x-show="saving">Saving...</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@once
    <script>
        function quickAddClient() {
            return {
                open: false,
                saving: false,
                clientType: 'individual',
                name: '',
                companyName: '',
                email: '',
                phone: '',
                errors: {},
                generalError: '',
                close() {
                    this.open = false;
                    this.clientType = 'individual';
                    this.name = '';
                    this.companyName = '';
                    this.email = '';
                    this.phone = '';
                    this.errors = {};
                    this.generalError = '';
                },
                save() {
                    if (this.saving) return;
                    this.saving = true;
                    this.errors = {};
                    this.generalError = '';

                    fetch('{{ route('clients.store') }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        },
                        body: JSON.stringify({
                            name: this.name,
                            client_type: this.clientType,
                            company_name: this.clientType === 'company' ? this.companyName : null,
                            email: this.email,
                            phone: this.phone,
                        }),
                    })
                    .then(async (response) => {
                        const data = await response.json().catch(() => ({}));
                        if (!response.ok) {
                            if (response.status === 422) {
                                this.errors = data.errors || {};
                                this.generalError = data.errors ? '' : (data.message || 'Could not create client.');
                            } else {
                                this.generalError = data.message || 'Could not create client.';
                            }
                            return;
                        }

                        const select = document.getElementById('client_id');
                        if (select) {
                            const option = document.createElement('option');
                            option.value = data.id;
                            option.textContent = data.select_label || data.name;
                            option.selected = true;
                            select.appendChild(option);
                            select.value = data.id;
                            select.dispatchEvent(new Event('change'));
                        }

                        this.close();
                    })
                    .catch(() => {
                        this.generalError = 'Something went wrong. Please try again.';
                    })
                    .finally(() => {
                        this.saving = false;
                    });
                },
            };
        }
    </script>
@endonce
