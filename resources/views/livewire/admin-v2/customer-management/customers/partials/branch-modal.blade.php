{{-- Dialog: Filiale anlegen bzw. bearbeiten --}}
<flux:modal name="customer-branch" class="md:w-[44rem]">
    <form wire:submit="saveBranch" class="flex flex-col gap-5">
        <flux:heading size="lg">{{ $editingBranchId ? 'Filiale bearbeiten' : 'Neue Filiale hinzufügen' }}</flux:heading>

        <div class="grid items-start gap-5 sm:grid-cols-2">
            <flux:field>
                <flux:label>App-Code</flux:label>
                <flux:input :value="$branchAppCode" class="font-mono" disabled />
                <flux:description>Wird automatisch generiert</flux:description>
            </flux:field>
            <flux:field>
                <flux:label>Filialname</flux:label>
                <flux:input wire:model="branchForm.name" maxlength="255" />
                <flux:error name="branchForm.name" />
            </flux:field>
            <flux:field>
                <flux:label>Zusatz</flux:label>
                <flux:input wire:model="branchForm.additional" maxlength="255" />
                <flux:error name="branchForm.additional" />
            </flux:field>
            <div class="flex items-end sm:pb-2 sm:pt-8">
                <flux:checkbox wire:model="branchForm.is_headquarters" label="Hauptsitz" />
            </div>
        </div>

        <div class="grid items-start gap-5 sm:grid-cols-2">
            <flux:field>
                <flux:label>Straße</flux:label>
                <flux:input wire:model="branchForm.street" maxlength="255" />
                <flux:error name="branchForm.street" />
            </flux:field>
            <flux:field>
                <flux:label>Hausnummer</flux:label>
                <flux:input wire:model="branchForm.house_number" maxlength="20" />
                <flux:error name="branchForm.house_number" />
            </flux:field>
            <flux:field>
                <flux:label>PLZ</flux:label>
                <flux:input wire:model="branchForm.postal_code" maxlength="20" />
                <flux:error name="branchForm.postal_code" />
            </flux:field>
            <flux:field>
                <flux:label>Stadt</flux:label>
                <flux:input wire:model="branchForm.city" maxlength="255" />
                <flux:error name="branchForm.city" />
            </flux:field>
            <flux:field>
                <flux:label>Land</flux:label>
                <flux:input wire:model="branchForm.country" maxlength="255" />
                <flux:error name="branchForm.country" />
            </flux:field>
        </div>

        <div class="grid items-start gap-5 sm:grid-cols-2">
            <flux:field>
                <flux:label>Breitengrad</flux:label>
                <flux:input wire:model="branchForm.latitude" inputmode="decimal" placeholder="z. B. 50.93750000" />
                <flux:description>Optional: Für Kartendarstellung</flux:description>
                <flux:error name="branchForm.latitude" />
            </flux:field>
            <flux:field>
                <flux:label>Längengrad</flux:label>
                <flux:input wire:model="branchForm.longitude" inputmode="decimal" placeholder="z. B. 6.96030000" />
                <flux:description>Optional: Für Kartendarstellung</flux:description>
                <flux:error name="branchForm.longitude" />
            </flux:field>
        </div>

        <div class="flex justify-end gap-2">
            <flux:modal.close><flux:button variant="ghost">Abbrechen</flux:button></flux:modal.close>
            <flux:button type="submit" variant="primary" icon="check" wire:loading.attr="disabled" wire:target="saveBranch">{{ $editingBranchId ? 'Speichern' : 'Erstellen' }}</flux:button>
        </div>
    </form>
</flux:modal>
