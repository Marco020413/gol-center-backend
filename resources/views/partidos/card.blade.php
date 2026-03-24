<div class="bg-slate-900 border border-slate-800 rounded-xl overflow-hidden hover:border-blue-500/50 transition-all duration-300 shadow-lg mb-4">
    <div class="p-4 flex items-center justify-between">
        <div class="flex-1 space-y-3">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <img src="{{ $partido['escudo_local'] }}" class="size-8 object-contain">
                    <span class="font-bold text-slate-200">{{ $partido['equipo_local'] }}</span>
                </div>
                <span class="text-2xl font-black text-white">{{ $partido['goles_local'] }}</span>
            </div>
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <img src="{{ $partido['escudo_visitante'] }}" class="size-8 object-contain">
                    <span class="font-bold text-slate-200">{{ $partido['equipo_visitante'] }}</span>
                </div>
                <span class="text-2xl font-black text-white">{{ $partido['goles_visitante'] }}</span>
            </div>
        </div>
        
        <button onclick="abrirActualizarMarcador('${id}')" class="mt-2 text-[10px] bg-blue-600/10 text-blue-500 border border-blue-500/20 px-2 py-1 rounded hover:bg-blue-600 hover:text-white transition">
            EDITAR
        </button>

        <div class="w-px h-12 bg-slate-800 mx-6"></div>

        <div class="text-right min-w-[80px]">
            <p class="text-[10px] font-black uppercase {{ $partido['estatus'] == 'en_curso' ? 'text-green-500 animate-pulse' : 'text-slate-500' }}">
                {{ $partido['estatus'] }}
            </p>
            <p class="text-xs text-slate-400 font-medium">{{ $partido['fecha_formateada'] }}</p>
            
            @if($partido['estatus'] !== 'finalizado')
                <button onclick="abrirActualizarMarcador('{{ $id }}')" class="mt-2 text-[10px] bg-blue-600/10 text-blue-500 border border-blue-500/20 px-2 py-1 rounded hover:bg-blue-600 hover:text-white transition">
                    EDITAR
                </button>
            @endif
        </div>
    </div>
</div>