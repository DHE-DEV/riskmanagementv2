{{-- Branch Form Modal --}}
<div x-show="showNewForm" x-cloak class="fixed inset-0 z-[10000] flex items-center justify-center" @keydown.escape.window="if(showNewForm) { showNewForm = false; resetNewForm(); }">
    {{-- Backdrop --}}
    <div class="absolute inset-0 bg-black bg-opacity-50" @click="showNewForm = false; resetNewForm()"></div>

    {{-- Modal --}}
    <div class="relative bg-white rounded-xl shadow-2xl w-full max-w-5xl mx-4 flex flex-col" style="max-height: calc(100vh - 100px);">
        {{-- Header --}}
        <div class="bg-gray-50 border-b border-gray-200 px-6 py-4 flex items-center justify-between rounded-t-xl flex-shrink-0">
            <h4 class="text-sm font-semibold text-gray-900 flex items-center gap-2">
                <i class="fas fa-building-circle-check text-blue-600"></i>
                <span x-text="newForm.editId ? 'Adresse bearbeiten' : 'Neue Adresse erfassen'"></span>
            </h4>
            <button @click="showNewForm = false; resetNewForm()" class="text-gray-400 hover:text-gray-600 p-1"><i class="fas fa-times text-lg"></i></button>
        </div>

        {{-- Tabs --}}
        <div class="flex border-b border-gray-200 px-6 flex-shrink-0">
            <button @click="branchTab = 'adresse'"
                class="px-4 py-2.5 text-xs font-medium border-b-2 transition-colors"
                :class="branchTab === 'adresse' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700'">
                <i class="fas fa-map-marker-alt mr-1"></i> Adresse
            </button>
            <button @click="branchTab = 'rufnummern'"
                class="px-4 py-2.5 text-xs font-medium border-b-2 transition-colors"
                :class="branchTab === 'rufnummern' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700'">
                <i class="fas fa-phone mr-1"></i> Rufnummern
                <span x-show="branchPhones.length" class="ml-1 bg-gray-200 text-gray-600 rounded-full px-1.5 text-[10px]" x-text="branchPhones.length"></span>
            </button>
            <button @click="branchTab = 'email'"
                class="px-4 py-2.5 text-xs font-medium border-b-2 transition-colors"
                :class="branchTab === 'email' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700'">
                <i class="fas fa-envelope mr-1"></i> E-Mail
                <span x-show="branchEmails.length" class="ml-1 bg-gray-200 text-gray-600 rounded-full px-1.5 text-[10px]" x-text="branchEmails.length"></span>
            </button>
            <button @click="branchTab = 'web'"
                class="px-4 py-2.5 text-xs font-medium border-b-2 transition-colors"
                :class="branchTab === 'web' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700'">
                <i class="fas fa-globe mr-1"></i> Web
                <span x-show="branchWebs.length" class="ml-1 bg-gray-200 text-gray-600 rounded-full px-1.5 text-[10px]" x-text="branchWebs.length"></span>
            </button>
            <button @click="branchTab = 'zuordnung'"
                class="px-4 py-2.5 text-xs font-medium border-b-2 transition-colors"
                :class="branchTab === 'zuordnung' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700'">
                <i class="fas fa-sitemap mr-1"></i> Zuordnung
            </button>
            <button @click="branchTab = 'kontakte'"
                class="px-4 py-2.5 text-xs font-medium border-b-2 transition-colors"
                :class="branchTab === 'kontakte' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700'">
                <i class="fas fa-address-card mr-1"></i> Kontakte
                <span x-show="branchContacts.length" class="ml-1 bg-gray-200 text-gray-600 rounded-full px-1.5 text-[10px]" x-text="branchContacts.length"></span>
            </button>
        </div>

        {{-- Content (scrollbar) --}}
        <div class="flex-1 overflow-y-auto p-6">
            {{-- Tab: Adresse --}}
            <div x-show="branchTab === 'adresse'">
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                    <div class="sm:col-span-2 lg:col-span-3">
                        <label class="block text-xs font-medium text-gray-700 mb-1">Name <span class="text-red-500">*</span></label>
                        <input type="text" x-model="newForm.name" required class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500" placeholder="Name der Adresse / Standort">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">Zusatz</label>
                        <input type="text" x-model="newForm.additional" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">Straße <span class="text-red-500">*</span></label>
                        <input type="text" x-model="newForm.street" required class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">Hausnummer</label>
                        <input type="text" x-model="newForm.house_number" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">PLZ <span class="text-red-500">*</span></label>
                        <input type="text" x-model="newForm.postal_code" required class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">Stadt <span class="text-red-500">*</span></label>
                        <input type="text" x-model="newForm.city" required class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">Land <span class="text-red-500">*</span></label>
                        <input type="text" x-model="newForm.country" required class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    </div>
                </div>
            </div>

            {{-- Tab: Rufnummern --}}
            <div x-show="branchTab === 'rufnummern'" x-cloak>
                <div class="flex items-center justify-between mb-3">
                    <p class="text-xs text-gray-500"><span x-text="branchPhones.length"></span> Rufnummern</p>
                    <button type="button" @click="branchPhones.push({label:'',number:'',type:'phone',notes:''})" class="px-2 py-1 bg-blue-600 text-white rounded text-[10px] hover:bg-blue-700 flex items-center gap-1"><i class="fas fa-plus"></i> Hinzufügen</button>
                </div>
                <template x-if="branchPhones.length === 0">
                    <p class="text-xs text-gray-400 text-center py-4">Noch keine Rufnummern. Klicken Sie auf "Hinzufügen".</p>
                </template>
                <div class="space-y-3">
                    <template x-for="(phone, idx) in branchPhones" :key="idx">
                        <div class="bg-gray-50 rounded-lg p-3 border border-gray-200 relative">
                            <button type="button" @click="branchPhones.splice(idx, 1)" class="absolute top-2 right-2 text-gray-400 hover:text-red-500 text-xs"><i class="fas fa-times"></i></button>
                            <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
                                <div>
                                    <label class="block text-[10px] font-medium text-gray-600 mb-1">Bezeichnung</label>
                                    <input type="text" x-model="phone.label" class="w-full px-2.5 py-1.5 border border-gray-300 rounded text-xs focus:ring-1 focus:ring-blue-500" placeholder="z.B. Zentrale">
                                </div>
                                <div>
                                    <label class="block text-[10px] font-medium text-gray-600 mb-1">Rufnummer</label>
                                    <input type="text" x-model="phone.number" class="w-full px-2.5 py-1.5 border border-gray-300 rounded text-xs focus:ring-1 focus:ring-blue-500" placeholder="+49 ...">
                                </div>
                                <div>
                                    <label class="block text-[10px] font-medium text-gray-600 mb-1">Typ</label>
                                    <select x-model="phone.type" class="w-full px-2.5 py-1.5 border border-gray-300 rounded text-xs focus:ring-1 focus:ring-blue-500 bg-white">
                                        <option value="phone">Festnetz</option><option value="mobile">Mobil</option><option value="fax">Fax</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-[10px] font-medium text-gray-600 mb-1">Notiz</label>
                                    <input type="text" x-model="phone.notes" class="w-full px-2.5 py-1.5 border border-gray-300 rounded text-xs focus:ring-1 focus:ring-blue-500" placeholder="Optional">
                                </div>
                            </div>
                        </div>
                    </template>
                </div>
            </div>

            {{-- Tab: E-Mail --}}
            <div x-show="branchTab === 'email'" x-cloak>
                <div class="flex items-center justify-between mb-3">
                    <p class="text-xs text-gray-500"><span x-text="branchEmails.length"></span> E-Mail Adressen</p>
                    <button type="button" @click="branchEmails.push({label:'',email:'',notes:''})" class="px-2 py-1 bg-blue-600 text-white rounded text-[10px] hover:bg-blue-700 flex items-center gap-1"><i class="fas fa-plus"></i> Hinzufügen</button>
                </div>
                <template x-if="branchEmails.length === 0">
                    <p class="text-xs text-gray-400 text-center py-4">Noch keine E-Mail Adressen. Klicken Sie auf "Hinzufügen".</p>
                </template>
                <div class="space-y-3">
                    <template x-for="(em, idx) in branchEmails" :key="idx">
                        <div class="bg-gray-50 rounded-lg p-3 border border-gray-200 relative">
                            <button type="button" @click="branchEmails.splice(idx, 1)" class="absolute top-2 right-2 text-gray-400 hover:text-red-500 text-xs"><i class="fas fa-times"></i></button>
                            <div class="grid grid-cols-1 lg:grid-cols-3 gap-3">
                                <div>
                                    <label class="block text-[10px] font-medium text-gray-600 mb-1">Bezeichnung</label>
                                    <input type="text" x-model="em.label" class="w-full px-2.5 py-1.5 border border-gray-300 rounded text-xs focus:ring-1 focus:ring-blue-500" placeholder="z.B. Info, Support">
                                </div>
                                <div>
                                    <label class="block text-[10px] font-medium text-gray-600 mb-1">E-Mail Adresse</label>
                                    <input type="email" x-model="em.email" class="w-full px-2.5 py-1.5 border border-gray-300 rounded text-xs focus:ring-1 focus:ring-blue-500" placeholder="adresse@firma.de">
                                </div>
                                <div>
                                    <label class="block text-[10px] font-medium text-gray-600 mb-1">Notiz</label>
                                    <input type="text" x-model="em.notes" class="w-full px-2.5 py-1.5 border border-gray-300 rounded text-xs focus:ring-1 focus:ring-blue-500" placeholder="Optional">
                                </div>
                            </div>
                        </div>
                    </template>
                </div>
            </div>

            {{-- Tab: Web --}}
            <div x-show="branchTab === 'web'" x-cloak>
                <div class="flex items-center justify-between mb-3">
                    <p class="text-xs text-gray-500"><span x-text="branchWebs.length"></span> Websites</p>
                    <button type="button" @click="branchWebs.push({label:'',url:'',notes:''})" class="px-2 py-1 bg-blue-600 text-white rounded text-[10px] hover:bg-blue-700 flex items-center gap-1"><i class="fas fa-plus"></i> Hinzufügen</button>
                </div>
                <template x-if="branchWebs.length === 0">
                    <p class="text-xs text-gray-400 text-center py-4">Noch keine Websites. Klicken Sie auf "Hinzufügen".</p>
                </template>
                <div class="space-y-3">
                    <template x-for="(web, idx) in branchWebs" :key="idx">
                        <div class="bg-gray-50 rounded-lg p-3 border border-gray-200 relative">
                            <button type="button" @click="branchWebs.splice(idx, 1)" class="absolute top-2 right-2 text-gray-400 hover:text-red-500 text-xs"><i class="fas fa-times"></i></button>
                            <div class="grid grid-cols-1 lg:grid-cols-3 gap-3">
                                <div>
                                    <label class="block text-[10px] font-medium text-gray-600 mb-1">Bezeichnung</label>
                                    <input type="text" x-model="web.label" class="w-full px-2.5 py-1.5 border border-gray-300 rounded text-xs focus:ring-1 focus:ring-blue-500" placeholder="z.B. Firmenseite">
                                </div>
                                <div>
                                    <label class="block text-[10px] font-medium text-gray-600 mb-1">URL</label>
                                    <input type="url" x-model="web.url" class="w-full px-2.5 py-1.5 border border-gray-300 rounded text-xs focus:ring-1 focus:ring-blue-500" placeholder="https://...">
                                </div>
                                <div>
                                    <label class="block text-[10px] font-medium text-gray-600 mb-1">Notiz</label>
                                    <input type="text" x-model="web.notes" class="w-full px-2.5 py-1.5 border border-gray-300 rounded text-xs focus:ring-1 focus:ring-blue-500" placeholder="Optional">
                                </div>
                            </div>
                        </div>
                    </template>
                </div>
            </div>

            {{-- Tab: Zuordnung --}}
            <div x-show="branchTab === 'zuordnung'" x-cloak>
                <h5 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-3 flex items-center gap-2">
                    <i class="fas fa-sitemap text-gray-400"></i> Zuordnung zur Organisationsstruktur
                    <span class="text-[10px] font-normal normal-case text-gray-400">(Mehrfachauswahl mit Kunden- &amp; Vertragsnummer und Zeitraum)</span>
                </h5>
                <div x-show="orgNodes.length === 0" class="bg-gray-50 rounded-lg p-4 text-center">
                    <p class="text-xs text-gray-500">Noch keine Organisationsstruktur vorhanden.</p>
                    <button type="button" @click="showNewForm = false; orgTab = 'struktur'" class="text-xs text-blue-600 hover:text-blue-800 mt-1">Struktur erstellen</button>
                </div>
                <div x-show="orgNodes.length > 0" x-html="renderOrgCheckboxWithFields()"></div>
            </div>

            {{-- Tab: Kontakte --}}
            <div x-show="branchTab === 'kontakte'" x-cloak>
                <div class="flex items-center justify-between mb-3">
                    <p class="text-xs text-gray-500"><span x-text="branchContacts.length"></span> Kontakte</p>
                    <button type="button" @click="branchContacts.push({salutation:'',title:'',first_name:'',last_name:'',function:'',department:'',phone:'',mobile:'',fax:'',email:'',notes:''})" class="px-2 py-1 bg-blue-600 text-white rounded text-[10px] hover:bg-blue-700 flex items-center gap-1"><i class="fas fa-plus"></i> Kontakt hinzufügen</button>
                </div>
                <template x-if="branchContacts.length === 0">
                    <p class="text-xs text-gray-400 text-center py-4">Noch keine Kontakte. Klicken Sie auf "Kontakt hinzufügen".</p>
                </template>
                <div class="space-y-4">
                    <template x-for="(contact, idx) in branchContacts" :key="idx">
                        <div class="bg-gray-50 rounded-lg border border-gray-200 overflow-hidden">
                            <div class="bg-gray-100 border-b border-gray-200 px-3 py-2 flex items-center justify-between">
                                <span class="text-[10px] font-semibold text-gray-500 uppercase tracking-wider">Kontakt <span x-text="idx + 1"></span></span>
                                <button type="button" @click="branchContacts.splice(idx, 1)" class="text-gray-400 hover:text-red-500 text-xs" title="Entfernen"><i class="fas fa-times"></i></button>
                            </div>
                            <div class="p-3 space-y-3">
                                {{-- Zeile 1: Anrede, Titel, Vorname, Nachname --}}
                                <div class="grid grid-cols-2 sm:grid-cols-4 lg:grid-cols-6 gap-3">
                                    <div>
                                        <label class="block text-[10px] font-medium text-gray-600 mb-1">Anrede</label>
                                        <select x-model="contact.salutation" class="w-full px-2.5 py-1.5 border border-gray-300 rounded text-xs focus:ring-1 focus:ring-blue-500 bg-white">
                                            <option value="">--</option>
                                            <option value="herr">Herr</option>
                                            <option value="frau">Frau</option>
                                            <option value="divers">Divers</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block text-[10px] font-medium text-gray-600 mb-1">Titel</label>
                                        <select x-model="contact.title" class="w-full px-2.5 py-1.5 border border-gray-300 rounded text-xs focus:ring-1 focus:ring-blue-500 bg-white">
                                            <option value="">--</option>
                                            <option value="Dr.">Dr.</option>
                                            <option value="Prof.">Prof.</option>
                                            <option value="Prof. Dr.">Prof. Dr.</option>
                                        </select>
                                    </div>
                                    <div class="col-span-2">
                                        <label class="block text-[10px] font-medium text-gray-600 mb-1">Vorname</label>
                                        <input type="text" x-model="contact.first_name" class="w-full px-2.5 py-1.5 border border-gray-300 rounded text-xs focus:ring-1 focus:ring-blue-500" placeholder="Vorname">
                                    </div>
                                    <div class="col-span-2">
                                        <label class="block text-[10px] font-medium text-gray-600 mb-1">Nachname</label>
                                        <input type="text" x-model="contact.last_name" class="w-full px-2.5 py-1.5 border border-gray-300 rounded text-xs focus:ring-1 focus:ring-blue-500" placeholder="Nachname">
                                    </div>
                                </div>
                                {{-- Zeile 2: Funktion, Abteilung --}}
                                <div class="grid grid-cols-2 gap-3">
                                    <div>
                                        <label class="block text-[10px] font-medium text-gray-600 mb-1">Funktion</label>
                                        <input type="text" x-model="contact.function" class="w-full px-2.5 py-1.5 border border-gray-300 rounded text-xs focus:ring-1 focus:ring-blue-500" placeholder="z.B. Geschäftsführer">
                                    </div>
                                    <div>
                                        <label class="block text-[10px] font-medium text-gray-600 mb-1">Abteilung</label>
                                        <input type="text" x-model="contact.department" class="w-full px-2.5 py-1.5 border border-gray-300 rounded text-xs focus:ring-1 focus:ring-blue-500" placeholder="z.B. Vertrieb">
                                    </div>
                                </div>
                                {{-- Zeile 3: Telefon, Mobil, Fax, E-Mail --}}
                                <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
                                    <div>
                                        <label class="block text-[10px] font-medium text-gray-600 mb-1">Telefon</label>
                                        <div class="relative">
                                            <span class="absolute inset-y-0 left-0 flex items-center pl-2 text-gray-400"><i class="fas fa-phone text-[9px]"></i></span>
                                            <input type="text" x-model="contact.phone" class="w-full pl-7 pr-2 py-1.5 border border-gray-300 rounded text-xs focus:ring-1 focus:ring-blue-500" placeholder="+49 ...">
                                        </div>
                                    </div>
                                    <div>
                                        <label class="block text-[10px] font-medium text-gray-600 mb-1">Mobil</label>
                                        <div class="relative">
                                            <span class="absolute inset-y-0 left-0 flex items-center pl-2 text-gray-400"><i class="fas fa-mobile-screen text-[9px]"></i></span>
                                            <input type="text" x-model="contact.mobile" class="w-full pl-7 pr-2 py-1.5 border border-gray-300 rounded text-xs focus:ring-1 focus:ring-blue-500" placeholder="+49 1...">
                                        </div>
                                    </div>
                                    <div>
                                        <label class="block text-[10px] font-medium text-gray-600 mb-1">Fax</label>
                                        <div class="relative">
                                            <span class="absolute inset-y-0 left-0 flex items-center pl-2 text-gray-400"><i class="fas fa-fax text-[9px]"></i></span>
                                            <input type="text" x-model="contact.fax" class="w-full pl-7 pr-2 py-1.5 border border-gray-300 rounded text-xs focus:ring-1 focus:ring-blue-500" placeholder="+49 ...">
                                        </div>
                                    </div>
                                    <div>
                                        <label class="block text-[10px] font-medium text-gray-600 mb-1">E-Mail</label>
                                        <div class="relative">
                                            <span class="absolute inset-y-0 left-0 flex items-center pl-2 text-gray-400"><i class="fas fa-envelope text-[9px]"></i></span>
                                            <input type="email" x-model="contact.email" class="w-full pl-7 pr-2 py-1.5 border border-gray-300 rounded text-xs focus:ring-1 focus:ring-blue-500" placeholder="name@firma.de">
                                        </div>
                                    </div>
                                </div>
                                {{-- Zeile 4: Notiz --}}
                                <div>
                                    <label class="block text-[10px] font-medium text-gray-600 mb-1">Notiz</label>
                                    <textarea x-model="contact.notes" rows="3" class="w-full px-2.5 py-1.5 border border-gray-300 rounded text-xs focus:ring-1 focus:ring-blue-500" placeholder="Interne Anmerkung"></textarea>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
        </div>

        {{-- Footer (fixiert unten) --}}
        <div class="flex items-center justify-end gap-2 px-6 py-4 border-t border-gray-200 bg-gray-50 rounded-b-xl flex-shrink-0">
            <button type="button" @click="showNewForm = false; resetNewForm()" class="px-4 py-2 text-xs text-gray-700 bg-white border border-gray-300 hover:bg-gray-50 rounded-lg transition-colors">Abbrechen</button>
            <button type="button" @click="saveNewBranch()" class="px-5 py-2 text-xs text-white bg-blue-600 hover:bg-blue-700 rounded-lg transition-colors flex items-center gap-1">
                <i class="fas fa-save"></i> <span x-text="newForm.editId ? 'Aktualisieren' : 'Speichern'"></span>
            </button>
        </div>
    </div>
</div>
