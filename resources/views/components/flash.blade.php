@if(session('sukses') || session('gagal') || $errors->any())
    <div class="mx-auto max-w-7xl px-4 pt-6 sm:px-6 lg:px-8">
        @if(session('sukses'))
            <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 5000)" x-transition
                 class="mb-4 flex items-start gap-3 rounded-2xl bg-success/10 px-4 py-3 text-sm text-success">
                <x-icon name="check-circle" class="mt-0.5 h-5 w-5 flex-shrink-0"/>
                <p class="flex-1">{{ session('sukses') }}</p>
                <button @click="show = false" type="button"><x-icon name="x" class="h-4 w-4"/></button>
            </div>
        @endif

        @if(session('gagal'))
            <div x-data="{ show: true }" x-show="show" x-transition
                 class="mb-4 flex items-start gap-3 rounded-2xl bg-danger/10 px-4 py-3 text-sm text-danger">
                <x-icon name="alert" class="mt-0.5 h-5 w-5 flex-shrink-0"/>
                <p class="flex-1">{{ session('gagal') }}</p>
                <button @click="show = false" type="button"><x-icon name="x" class="h-4 w-4"/></button>
            </div>
        @endif

        @if($errors->any())
            <div x-data="{ show: true }" x-show="show" x-transition
                 class="mb-4 flex items-start gap-3 rounded-2xl bg-danger/10 px-4 py-3 text-sm text-danger">
                <x-icon name="alert" class="mt-0.5 h-5 w-5 flex-shrink-0"/>
                <div class="flex-1">
                    <p class="font-medium">Periksa kembali isian formulir:</p>
                    <ul class="mt-1 list-inside list-disc space-y-0.5">
                        @foreach($errors->all() as $pesan)
                            <li>{{ $pesan }}</li>
                        @endforeach
                    </ul>
                </div>
                <button @click="show = false" type="button"><x-icon name="x" class="h-4 w-4"/></button>
            </div>
        @endif
    </div>
@endif
