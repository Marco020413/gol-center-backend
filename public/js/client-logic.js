const escudoDefault = 'https://cdn-icons-png.flaticon.com/512/5323/5323982.png';

// Cache para evitar múltiples llamadas
let cacheData = null;
let cacheTime = 0;
const CACHE_DURATION = 30000; // 30 segundos

let datosGlobales = null; // Guardar datos para usar en modal

// Cargar datos al iniciar - UNA SOLA LLAMADA
document.addEventListener('DOMContentLoaded', async () => {
    const contenedorCarga = document.getElementById('contenedor-goleadores');
    if (contenedorCarga) {
        contenedorCarga.innerHTML = '<div class="col-span-full text-center py-8 text-slate-500">Cargando datos...</div>';
    }
    
    // UNA SOLA LLAMADA API para TODO
    const res = await fetch('/api/publico');
    datosGlobales = await res.json(); // Guardar globally
    
    // Renderizar cada sección
    cargarGoleadores(datosGlobales.jugadores);
    cargarJugadores(datosGlobales.jugadores);
    cargarEquipos(datosGlobales.equipos);
    cargarPosiciones(datosGlobales.equipos, datosGlobales.partidos);
    cargarPartidos(datosGlobales.partidos);
    cargarLiguilla(datosGlobales.partidos);
    cargarRoles(datosGlobales.campos);
});

// === MODAL DE EQUIPO ===
function mostrarDetalleEquipo(nombreEquipo) {
    const partidos = datosGlobales.partidos;
    const jugadores = datosGlobales.jugadores;
    const equipoPartidos = [];
    
    // Buscar todos los partidos del equipo
    Object.values(partidos).forEach(p => {
        const esLocal = p.equipo_local === nombreEquipo;
        const esVisitante = p.equipo_visitante === nombreEquipo;
        
        if (esLocal || esVisitante) {
            let resultado = 'pendiente';
            if (p.resultado_confirmado) {
                const gl = p.goles_local || 0;
                const gv = p.goles_visitante || 0;
                
                if (esLocal) {
                    resultado = gl > gv ? 'V' : (gl < gv ? 'D' : 'E');
                } else {
                    resultado = gv > gl ? 'V' : (gv < gl ? 'D' : 'E');
                }
            }
            
            equipoPartidos.push({
                rival: esLocal ? p.equipo_visitante : p.equipo_local,
                esLocal: esLocal,
                resultado: resultado,
                fecha: p.fecha || '',
                jornada: p.jornada || ''
            });
        }
    });
    
    // Ordenar por fecha (más reciente primero)
    equipoPartidos.sort((a, b) => {
        if (!a.fecha) return 1;
        if (!b.fecha) return -1;
        return new Date(b.fecha) - new Date(a.fecha);
    });
    
    // Últimos 5 partidos
    const ultimos5 = equipoPartidos.slice(0, 5);
    
    // Calcular stats
    const totalJugados = equipoPartidos.filter(p => p.resultado !== 'pendiente').length;
    const totalVictorias = equipoPartidos.filter(p => p.resultado === 'V').length;
    const totalEmpates = equipoPartidos.filter(p => p.resultado === 'E').length;
    const totalDerrotas = equipoPartidos.filter(p => p.resultado === 'D').length;
    
    // Buscar jugadores del equipo (ordenar por puntos = goles + asists)
    const equipoJugadores = Object.values(jugadores || {})
        .filter(j => j.equipo === nombreEquipo)
        .sort((a, b) => {
            const ptsA = (a.goles || 0) + (a.asistencias || 0);
            const ptsB = (b.goles || 0) + (b.asistencias || 0);
            return ptsB - ptsA;
        });
    
    // Crear HTML del modal
    const modal = document.createElement('div');
    modal.id = 'modal-equipo';
    modal.className = 'fixed inset-0 bg-black/80 backdrop-blur-sm z-50 flex items-center justify-center p-4';
    modal.innerHTML = `
        <div class="glass-card rounded-2xl p-6 max-w-md w-full max-h-[90vh] overflow-y-auto">
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-xl font-black text-white uppercase">${nombreEquipo}</h2>
                <button onclick="cerrarModalEquipo()" class="text-slate-400 hover:text-white text-2xl">&times;</button>
            </div>
            
            <!-- Stats Generales -->
            <div class="grid grid-cols-4 gap-2 mb-6">
                <div class="bg-emerald-500/20 rounded-lg p-3 text-center">
                    <div class="text-2xl font-black text-emerald-400">${totalVictorias}</div>
                    <div class="text-[10px] text-slate-400 uppercase">Victorias</div>
                </div>
                <div class="bg-amber-500/20 rounded-lg p-3 text-center">
                    <div class="text-2xl font-black text-amber-400">${totalEmpates}</div>
                    <div class="text-[10px] text-slate-400 uppercase">Empates</div>
                </div>
                <div class="bg-rose-500/20 rounded-lg p-3 text-center">
                    <div class="text-2xl font-black text-rose-400">${totalDerrotas}</div>
                    <div class="text-[10px] text-slate-400 uppercase">Derrotas</div>
                </div>
                <div class="bg-blue-500/20 rounded-lg p-3 text-center">
                    <div class="text-2xl font-black text-blue-400">${totalJugados}</div>
                    <div class="text-[10px] text-slate-400 uppercase">Jugados</div>
                </div>
            </div>
            
            <!-- Jugadores del Equipo -->
            <h3 class="text-sm font-bold text-slate-400 uppercase mb-3 flex items-center gap-2">
                <span>👥</span> Plantilla (${equipoJugadores.length})
            </h3>
            <div class="grid grid-cols-2 gap-2 mb-6 max-h-[200px] overflow-y-auto">
                ${equipoJugadores.length === 0 
                    ? '<div class="col-span-2 text-slate-500 text-center py-2 text-xs">Sin jugadores registrados</div>' 
                    : equipoJugadores.slice(0, 12).map(j => `
                    <div onclick="mostrarDetalleJugador('${j.nombre.replace(/'/g, "\\'")}', '${j.equipo || ''}', ${j.goles || 0}, ${j.partidos_jugados || 0})" class="flex items-center justify-between p-2 bg-slate-800/50 rounded-lg text-xs cursor-pointer hover:bg-slate-700/50 transition-colors">
                        <span class="text-white font-bold truncate">${j.nombre || 'Sin nombre'}</span>
                        <span class="text-emerald-400 font-bold">${j.goles || 0} G</span>
                    </div>
                `).join('')}
            </div>
            
            <!-- Últimos 5 Partidos -->
            <h3 class="text-sm font-bold text-slate-400 uppercase mb-3 flex items-center gap-2">
                <span>📊</span> Últimos 5 Partidos
            </h3>
            <div class="space-y-2">
                ${ultimos5.length === 0 ? '<div class="text-slate-500 text-center py-4">Sin partidos jugados</div>' : ultimos5.map(p => {
                    const resultadoClass = p.resultado === 'V' ? 'bg-emerald-500' : p.resultado === 'D' ? 'bg-rose-500' : p.resultado === 'E' ? 'bg-amber-500' : 'bg-slate-600';
                    const resultadoText = p.resultado === 'V' ? 'V' : p.resultado === 'D' ? 'D' : p.resultado === 'E' ? 'E' : '-';
                    
                    return `
                    <div class="flex items-center justify-between p-3 bg-slate-800/50 rounded-lg">
                        <div class="flex items-center gap-2">
                            <span class="text-[10px] text-slate-500">${p.esLocal ? 'L' : 'V'}</span>
                            <span class="text-xs text-slate-300 truncate max-w-[120px]">${p.rival}</span>
                        </div>
                        <div class="w-8 h-8 ${resultadoClass} rounded flex items-center justify-center">
                            <span class="text-xs font-black text-white">${resultadoText}</span>
                        </div>
                    </div>`;
                }).join('')}
            </div>
        </div>
    `;
    
    document.body.appendChild(modal);
    modal.addEventListener('click', (e) => {
        if (e.target === modal) cerrarModalEquipo();
    });
}

function cerrarModalEquipo() {
    const modal = document.getElementById('modal-equipo');
    if (modal) modal.remove();
}

// === MODAL DE JUGADOR ===
function mostrarDetalleJugador(nombre, equipo, goles, pj) {
    // Cerrar modal de equipo
    cerrarModalEquipo();
    
    // Calcular promedio
    const promedio = pj > 0 ? (goles / pj).toFixed(1) : 0;
    
    // Generar análisis inteligente basado en stats
    const analizar = [];
    
    if (pj >= 5 && promedio >= 1) {
        analizar.push({ icon: '🔥', text: 'Goleador prolifico - Alta efectividad', color: 'text-emerald-400' });
    } else if (pj >= 3 && promedio >= 0.5) {
        analizar.push({ icon: '⚡', text: 'Jugador determinante', color: 'text-blue-400' });
    } else if (pj >= 1 && promedio < 0.3 && promedio > 0) {
        analizar.push({ icon: '🎯', text: 'En formación - Potencial en desarrollo', color: 'text-amber-400' });
    } else if (pj === 0) {
        analizar.push({ icon: '🌱', text: 'Sin actividad - Nuevo en el equipo', color: 'text-slate-400' });
    }
    
    if (goles >= 10) {
        analizar.push({ icon: '🏆', text: 'Candidato a bestia del tournament', color: 'text-amber-400' });
    } else if (goles >= 5) {
        analizar.push({ icon: '💎', text: 'Jugador valioso para el equipo', color: 'text-blue-400' });
    }
    
    if (pj > 0) {
        const tendencia = promedio >= 1 ? ' ascendiendo' : ' en progreso';
        analizar.push({ icon: '📈', text: 'Rendimiento' + tendencia, color: 'text-purple-400' });
    }
    
    const modal = document.createElement('div');
    modal.id = 'modal-jugador';
    modal.className = 'fixed inset-0 bg-black/80 backdrop-blur-sm z-50 flex items-center justify-center p-4';
    modal.innerHTML = `
        <div class="glass-card rounded-2xl p-6 max-w-sm w-full">
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-xl font-black text-white">${nombre}</h2>
                <button onclick="cerrarModalJugador()" class="text-slate-400 hover:text-white text-2xl">&times;</button>
            </div>
            
            <!-- Equipo -->
            <div class="text-center mb-6">
                <span class="px-4 py-2 bg-blue-500/20 rounded-full text-blue-400 text-sm font-bold">${equipo}</span>
            </div>
            
            <!-- Stats Principales -->
            <div class="grid grid-cols-2 gap-3 mb-6">
                <div class="bg-emerald-500/20 rounded-xl p-4 text-center">
                    <div class="text-4xl font-black text-emerald-400">${goles}</div>
                    <div class="text-[10px] text-slate-400 uppercase">Goles</div>
                </div>
                <div class="bg-purple-500/20 rounded-xl p-4 text-center">
                    <div class="text-4xl font-black text-purple-400">${promedio}</div>
                    <div class="text-[10px] text-slate-400 uppercase">x Partido</div>
                </div>
            </div>
            
            <!-- Stats Secundarios -->
            <div class="bg-slate-800/50 rounded-lg p-3 text-center mb-6">
                <div class="text-2xl font-bold text-white">${pj}</div>
                <div class="text-[10px] text-slate-400 uppercase">Partidos Jugados</div>
            </div>
            
            <!-- AI Analysis - Sección Futurista -->
            <div class="border border-purple-500/30 rounded-xl p-4 bg-gradient-to-b from-purple-500/10 to-transparent">
                <div class="flex items-center gap-2 mb-3">
                    <span class="text-lg">🤖</span>
                    <span class="text-sm font-bold text-purple-400 uppercase">AI Analysis</span>
                </div>
                <div class="space-y-2">
                    ${analizar.length === 0 ? '<div class="text-slate-500 text-xs">Sin datos suficientes para análisis</div>' : analizar.map(a => `
                        <div class="flex items-center gap-2 text-xs">
                            <span>${a.icon}</span>
                            <span class="${a.color}">${a.text}</span>
                        </div>
                    `).join('')}
                </div>
            </div>
        </div>
    `;
    
    document.body.appendChild(modal);
    modal.addEventListener('click', (e) => {
        if (e.target === modal) cerrarModalJugador();
    });
}

function cerrarModalJugador() {
    const modal = document.getElementById('modal-jugador');
    if (modal) modal.remove();
}

// === TODOS LOS EQUIPOS ===
function cargarEquipos(equipos) {
    const contenedor = document.getElementById('contenedor-equipos');
    if (!contenedor) return;
    
    const lista = Object.values(equipos || {});
    if (lista.length === 0) {
        contenedor.innerHTML = '<div class="col-span-full text-center py-8 text-slate-500">No hay equipos registrados.</div>';
        return;
    }

    contenedor.innerHTML = lista.map(e => `
        <div onclick="mostrarDetalleEquipo('${e.nombre}')" class="glass-card rounded-xl p-4 flex flex-col items-center gap-2 hover:border-blue-500/50 transition-all cursor-pointer">
            <img src="${e.escudo || escudoDefault}" class="escudo-small" style="width:40px; height:40px;">
            <span class="text-xs font-bold text-white text-center uppercase">${e.nombre}</span>
        </div>
    `).join('');
}

// === RANKING COMPLETO DE JUGADORES (COMPETITIVO) ===
function cargarJugadores(jugadores) {
    const contenedor = document.getElementById('contenedor-jugadores');
    if (!contenedor) return;

    const lista = Object.values(jugadores || {})
        .sort((a, b) => {
            const ptsA = (a.goles || 0) * 1 + (a.asistencias || 0) * 0.5;
            const ptsB = (b.goles || 0) * 1 + (b.asistencias || 0) * 0.5;
            return ptsB - ptsA;
        });

    if (lista.length === 0) {
        contenedor.innerHTML = '<div class="p-8 text-center text-slate-500">No hay jugadores registrados.</div>';
        return;
    }

    // Solo Top 10
    const top10 = lista.slice(0, 10);

    let html = `
        <div class="overflow-x-auto">
            <table class="w-full text-xs">
                <thead class="bg-slate-800/80 text-slate-400 uppercase tracking-wider text-[10px]">
                    <tr>
                        <th class="px-4 py-3 text-left">#</th>
                        <th class="px-4 py-3 text-left">Jugador</th>
                        <th class="px-4 py-3 text-center">Equipo</th>
                        <th class="px-4 py-3 text-center">PJ</th>
                        <th class="px-4 py-3 text-center text-emerald-400">G</th>
                        <th class="px-4 py-3 text-center text-blue-400">A</th>
                        <th class="px-4 py-3 text-center text-amber-400">PTS</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/50">
                    ${top10.map((j, i) => {
                        const pts = (j.goles || 0) * 1 + (j.asistencias || 0) * 0.5;
                        const rowClass = i < 3 ? 'bg-gradient-to-r from-amber-500/10 to-transparent' : 'hover:bg-slate-800/30';
                        const rankClass = i === 0 ? 'text-amber-400 font-black' :
                                        i === 1 ? 'text-slate-300 font-bold' :
                                        i === 2 ? 'text-orange-400 font-bold' : 'text-blue-400 font-bold';
                        
                        return `
                        <tr class="${rowClass} transition-colors">
                            <td class="px-4 py-3 ${rankClass}">${i + 1}</td>
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-2">
                                    <span class="text-lg">${i === 0 ? '🥇' : i === 1 ? '🥈' : i === 2 ? '🥉' : '⭐'}</span>
                                    <span class="font-bold text-white">${j.nombre || 'Sin nombre'}</span>
                                </div>
                            </td>
                            <td class="px-4 py-3 text-center">
                                <span class="px-2 py-1 rounded-full bg-slate-800 text-slate-400 text-[10px] font-bold">${j.equipo || '-'}</span>
                            </td>
                            <td class="px-4 py-3 text-center text-slate-400">${j.partidos_jugados || 0}</td>
                            <td class="px-4 py-3 text-center text-emerald-400 font-bold">${j.goles || 0}</td>
                            <td class="px-4 py-3 text-center text-blue-400 font-bold">${j.asistencias || 0}</td>
                            <td class="px-4 py-3 text-center">
                                <span class="px-2 py-1 rounded-full bg-amber-500/20 text-amber-400 font-bold">${Math.round(pts * 10) / 10}</span>
                            </td>
                        </tr>`;
                    }).join('')}
                </tbody>
            </table>
        </div>
        <div class="p-4 text-center text-slate-500 text-xs border-t border-slate-800/50">
            Los demas jugadores solo visible al seleccionar su equipo
        </div>
    `;

    contenedor.innerHTML = html;
}

// === FUNCION SWITCH TAB ===
function switchTab(tab) {
    document.querySelectorAll('.tab-content').forEach(c => c.classList.add('hidden'));
    document.querySelectorAll('.tab-btn').forEach(b => {
        b.classList.replace('bg-blue-600', 'bg-slate-900');
        b.classList.replace('text-white', 'text-slate-400');
        b.classList.remove('ring-2', 'ring-blue-500');
    });
    document.getElementById(`tab-${tab}`).classList.remove('hidden');
    const activeBtn = document.querySelector(`button[onclick="switchTab('${tab}')"]`);
    if (activeBtn) {
        activeBtn.classList.replace('bg-slate-900', 'bg-blue-600');
        activeBtn.classList.replace('text-slate-400', 'text-white');
    }
}

// TABLA DE GOLEADORES - TOP 3 HORIZONTAL
function cargarGoleadores(jugadores) {
    const contenedor = document.getElementById('contenedor-goleadores');
    if (!contenedor) return;

    const goleadores = Object.entries(jugadores || {})
        .filter(([tel, j]) => (j.goles || 0) > 0)
        .sort((a, b) => b[1].goles - a[1].goles)
        .slice(0, 3);

    if (goleadores.length === 0) {
        contenedor.innerHTML = '<div class="col-span-full text-center py-8 text-slate-500">Aún no hay goles registrados.</div>';
        return;
    }

    // TOP 3 - HORIZONTAL
    contenedor.innerHTML = `
        <div class="flex flex-col md:flex-row items-stretch md:items-end gap-4">
            ${goleadores.map(([telefono, j], i) => {
                const isFirst = i === 0;
                const orderClass = isFirst ? 'order-2 md:order-1 md:-mb-4' : i === 1 ? 'order-1 md:order-2' : 'order-3';
                const sizeClass = isFirst ? 'md:scale-110' : '';
                const medalEmoji = i === 0 ? '🥇' : i === 1 ? '🥈' : '🥉';
                const glowClass = isFirst ? 'ring-2 ring-amber-400/50 shadow-lg shadow-amber-500/20' : '';
                const bgGradient = isFirst ? 'from-amber-500/20 to-yellow-600/10' : 
                                   i === 1 ? 'from-slate-400/20 to-slate-600/10' : 
                                   'from-orange-700/20 to-orange-900/10';
                
                return `
                <div class="flex-1 ${orderClass} ${sizeClass}" onclick="abrirInfoJugador('${telefono}')" style="cursor:pointer">
                    <div class="glass-card rounded-2xl p-4 flex flex-col items-center ${glowClass} bg-gradient-to-b ${bgGradient} relative overflow-hidden h-full hover:scale-105 transition-transform">
                        <div class="absolute top-0 left-0 w-full h-1 bg-gradient-to-r ${isFirst ? 'from-amber-400 via-yellow-300 to-amber-400' : i === 1 ? 'from-slate-300 to-slate-400' : 'from-orange-400 to-orange-600'}"></div>
                        <div class="text-2xl mb-2">${medalEmoji}</div>
                        <div class="text-sm font-black text-white text-center leading-tight">${j.nombre}</div>
                        <div class="text-[10px] text-blue-400 uppercase">${j.equipo}</div>
                        <div class="mt-2 px-3 py-1 rounded-full ${isFirst ? 'bg-amber-500' : i === 1 ? 'bg-slate-400' : 'bg-orange-600'}">
                            <span class="text-lg font-black text-white">${j.goles}</span>
                        </div>
                    </div>
                </div>`;
            }).join('')}
        </div>`;
}

function cargarPosiciones(equipos, partidos) {
    const contenedor = document.getElementById('contenedor-posiciones');
    if (!contenedor) return;

    const stats = {};
    for (const id in equipos) {
        stats[equipos[id].nombre] = {
            nombre: equipos[id].nombre,
            escudo: equipos[id].escudo,
            pj: 0, g: 0, e: 0, p: 0, gf: 0, gc: 0, pts: 0
        };
    }

    Object.values(partidos || {}).forEach(partido => {
        if (partido.resultado_confirmado) {
            const loc = partido.equipo_local;
            const vis = partido.equipo_visitante;
            const gl = parseInt(partido.goles_local || 0);
            const gv = parseInt(partido.goles_visitante || 0);

            if (stats[loc] && stats[vis]) {
                stats[loc].pj++; stats[vis].pj++;
                stats[loc].gf += gl; stats[loc].gc += gv;
                stats[vis].gf += gv; stats[vis].gc += gl;
                if (gl > gv) { stats[loc].pts += 3; stats[loc].g++; stats[vis].p++; }
                else if (gv > gl) { stats[vis].pts += 3; stats[vis].g++; stats[loc].p++; }
                else { stats[loc].pts++; stats[vis].pts++; stats[loc].e++; stats[vis].e++; }
            }
        }
    });

    const tabla = Object.values(stats).sort((a, b) => b.pts - a.pts);

    if (tabla.length === 0) {
        contenedor.innerHTML = '<div class="p-10 text-center text-slate-500 italic">No hay datos disponibles aún</div>';
        return;
    }

    contenedor.innerHTML = tabla.map((t, i) => `
        <div class="grid grid-cols-12 gap-1 px-4 py-3 items-center table-row border-b border-slate-800/30 last:border-0 transition-colors">
            <div class="col-span-1 text-xs font-bold ${i < 3 ? 'text-blue-400' : 'text-slate-500'}">
                ${i + 1}
            </div>
            
            <div class="col-span-4 flex items-center gap-2">
                <img src="${t.escudo || escudoDefault}" class="escudo-small" style="width:20px; height:20px;">
                <span class="text-xs font-bold text-slate-200 truncate uppercase">${t.nombre}</span>
            </div>
            
            <div class="col-span-1 text-center text-[11px] text-slate-400">${t.pj}</div>
            <div class="col-span-1 text-center text-[11px] text-slate-400">${t.g}</div>
            <div class="col-span-1 text-center text-[11px] text-slate-400">${t.e}</div>
            <div class="col-span-1 text-center text-[11px] text-slate-400">${t.p}</div>
            <div class="col-span-1 text-center text-[11px] text-emerald-500/80">${t.gf}</div>
            <div class="col-span-1 text-center text-[11px] text-rose-500/80">${t.gc}</div>
            
            <div class="col-span-1 text-right text-xs font-black text-white">
                ${t.pts}
            </div>
        </div>
    `).join('');
}

function cargarPartidos(partidos) {
    const contenedor = document.getElementById('contenedor-partidos');
    if (!contenedor) return;

    const allPartidos = Object.values(partidos || {});
    const upcoming = allPartidos
        .filter(p => !p.resultado_confirmado && p.fecha && p.fecha !== 'PENDIENTE')
        .sort((a, b) => {
            const fechaA = new Date(a.fecha + ' ' + (a.hora || '00:00'));
            const fechaB = new Date(b.fecha + ' ' + (b.hora || '00:00'));
            return fechaA - fechaB;
        })
        .slice(0, 5);

    if (upcoming.length === 0) {
        contenedor.innerHTML = '<p class="sin-datos">No hay partidos programados.</p>';
        return;
    }

       contenedor.innerHTML = upcoming.map(p => `
            <div class="glass-card p-5 rounded-2xl group hover:border-blue-500/50 transition-all duration-300">
                <div class="flex justify-between items-center mb-4">
                    <span class="text-[10px] font-bold uppercase tracking-widest px-2 py-1 bg-slate-800 rounded text-slate-400">
                        Jornada ${p.jornada || '-'}
                    </span>
                    <span class="text-[10px] font-bold text-blue-400 uppercase">
                        ${p.fecha_formateada || p.fecha}
                    </span>
                </div>
                <div class="flex items-center justify-between gap-4">
                    <div class="flex-1 text-center">
                        <div class="text-sm font-bold text-white truncate">${p.equipo_local}</div>
                    </div>
                    <div class="px-3 py-1 bg-slate-800 rounded-lg text-[10px] font-black text-slate-500">VS</div>
                    <div class="flex-1 text-center">
                        <div class="text-sm font-bold text-white truncate">${p.equipo_visitante}</div>
                    </div>
                </div>
            </div>
        `).join('');
}

// === LIGUILLA / PLAY-OFFS ===
function cargarLiguilla(partidos) {
    const contenedor = document.getElementById('contenedor-liguilla');
    if (!contenedor) return;

    const liguilla = Object.values(partidos || {})
        .filter(p => {
            const esLiguilla = p.tipo === 'liguilla' || p.tipo === 'playoff';
            const esFaseEliminatoria = ['Cuartos', 'Semifinal', 'Final', 'Tercer Lugar'].includes(p.fase);
            const esFinalizado = p.estatus === 'finalizado' || p.resultado_confirmado;
            const tieneFase = p.fase || p.jornada;
            return (esLiguilla || esFaseEliminatoria || esFinalizado) && tieneFase;
        })
        .sort((a, b) => (a.jornada || 0) - (b.jornada || 0));

    if (liguilla.length === 0) {
        contenedor.innerHTML = '<div class="col-span-full text-center py-8 text-slate-500">Aún no hay fase de liguilla o play-offs.</div>';
        return;
    }

    const fases = {};
    liguilla.forEach(p => {
        let key = p.fase || p.jornada;
        if (!key && p.resultado_confirmado) key = 'Torneo Finalizado';
        if (!key) key = 'Eliminas';
        if (!fases[key]) fases[key] = [];
        fases[key].push(p);
    });

    const ordenFases = {'Cuartos': 1, 'Semifinal': 2, 'Final': 3, 'Tercer Lugar': 4, 'Torneo Finalizado': 5};
    const fasesOrdenadas = Object.entries(fases).sort((a, b) => {
        return (ordenFases[a[0]] || 99) - (ordenFases[b[0]] || 99);
    });

    contenedor.innerHTML = fasesOrdenadas.map(([fase, matches]) => {
        const esFinal = fase === 'Final' || fase === 'Torneo Finalizado';
        const faseClass = esFinal 
            ? 'from-purple-500/30 to-blue-500/30 border-purple-500/30' 
            : 'from-amber-500/20 to-orange-600/10 border-amber-500/20';
        const badge = fase === 'Torneo Finalizado' ? '🎖️' : esFinal ? '🏆' : '⚔️';
        
        return `
        <div class="glass-card rounded-xl p-4 border ${faseClass}">
            <h3 class="text-sm font-bold ${esFinal ? 'text-purple-400' : 'text-amber-400'} uppercase mb-4 text-center flex items-center justify-center gap-2">
                ${badge} ${fase}
            </h3>
            <div class="space-y-3">
                ${matches.map(p => {
                    const confirmado = p.resultado_confirmado;
                    const scoreClass = confirmado ? 'bg-emerald-500/20 text-emerald-400' : 'bg-slate-900 text-slate-500';
                    
                    return `
                    <div class="flex items-center justify-between p-3 bg-slate-800/50 rounded-lg border border-slate-700/50 ${confirmado ? 'border-l-4 border-l-emerald-500' : ''}">
                        <div class="flex-1 text-center">
                            <div class="text-sm font-bold text-white">${p.equipo_local || '?'}</div>
                        </div>
                        <div class="px-3 py-1 mx-2 ${scoreClass} rounded text-xs font-black">
                            ${confirmado ? `${p.goles_local} - ${p.goles_visitante}` : 'vs'}
                        </div>
                        <div class="flex-1 text-center">
                            <div class="text-sm font-bold text-white">${p.equipo_visitante || '?'}</div>
                        </div>
                    </div>
                `}).join('')}
            </div>
        </div>`;
    }).join('');
}

// === ROLES DE JUEGO (CAMPOS) ===
function cargarRoles(campos) {
    const contenedor = document.getElementById('contenedor-roles');
    if (!contenedor) return;

    const listaCampos = Object.values(campos || {});
    
    if (listaCampos.length === 0) {
        contenedor.innerHTML = '<div class="col-span-full text-center py-8 text-slate-500">No hay campos disponibles.</div>';
        return;
    }

    contenedor.innerHTML = `
        <div class="glass-card rounded-xl p-4">
            <h3 class="text-sm font-bold text-blue-400 uppercase mb-4 flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                Canchas Disponibles
            </h3>
            <div class="space-y-3">
                ${listaCampos.map(c => `
                    <div class="flex items-center justify-between p-3 bg-slate-800/50 rounded-lg border border-slate-700/50">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-lg bg-emerald-500/20 flex items-center justify-center">
                                <svg class="w-5 h-5 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7" /></svg>
                            </div>
                            <div>
                                <div class="text-sm font-bold text-white">${c.nombre || c.lugar || 'Campo sin nombre'}</div>
                                <div class="text-[10px] text-slate-400">${c.lugar || 'Sin ubicación'}</div>
                            </div>
                        </div>
                        <span class="text-xs font-bold ${c.estado === 'mantenimiento' ? 'text-rose-400 px-2 py-1 bg-rose-500/10 rounded' : 'text-emerald-400 px-2 py-1 bg-emerald-500/10 rounded'}">${c.estado === 'mantenimiento' ? 'Mantenimiento' : 'Activo'}</span>
                    </div>
                `).join('')}
            </div>
        </div>`;
}

// Función para abrir modal de info de jugador
function abrirInfoJugador(telefono) {
    const j = datosGlobales?.jugadores?.[telefono];
    if (!j) return;
    
    const modal = document.getElementById('modalInfoEquipo');
    if (!modal) return;
    
    const nombre = document.getElementById('modalEqNombre');
    const posicion = document.getElementById('modalEqPosicion');
    const stats = document.getElementById('modalEqStats');
    const jugadores = document.getElementById('modalEqJugadores');
    
    const dorsal = j.numero || 0;
    
    // Reemplazar la imagen del escudo por un círculo con el dorsal
    const leftContent = modal.querySelector('.flex.items-center.gap-3');
    if (leftContent) {
        leftContent.innerHTML = `
            <div class="size-12 rounded-full bg-blue-500/20 flex items-center justify-center">
                <span class="text-xl font-black text-blue-400">${dorsal}</span>
            </div>
            <div>
                <h3 id="modalEqNombre" class="text-xl font-black text-white uppercase">${j.nombre || 'Jugador'}</h3>
                <p id="modalEqPosicion" class="text-xs text-slate-400">${j.equipo || 'Sin equipo'}</p>
            </div>
        `;
    }
    
    // Mostrar stats
    stats.innerHTML = `
        <div class="bg-slate-800 rounded p-2"><div class="text-xs text-slate-400">Goles</div><div class="font-black text-emerald-400 text-lg">${j.goles || 0}</div></div>
        <div class="bg-slate-800 rounded p-2"><div class="text-xs text-slate-400">PJ</div><div class="font-black text-white text-lg">${j.partidos_jugados || 0}</div></div>
        <div class="bg-slate-800 rounded p-2"><div class="text-xs text-slate-400">Dorsal</div><div class="font-black text-blue-400 text-lg">${dorsal}</div></div>
    `;
    
    // Mostrar más info del jugador - stuff menarik!
    const pj = j.partidos_jugados || 0;
    const goles = j.goles || 0;
    const ratio = pj > 0 ? (goles / pj).toFixed(2) : '0.00';
    
    let infoExtra = '';
    if (j.edad) infoExtra += `<span class="bg-blue-500/20 text-blue-400 px-3 py-1 rounded-full text-xs font-bold">${j.edad} años</span> `;
    infoExtra += `<span class="bg-emerald-500/20 text-emerald-400 px-3 py-1 rounded-full text-xs font-bold">${ratio} gol/partido</span> `;
    if (j.estatus && j.estatus !== 'activo') infoExtra += `<span class="bg-red-500 px-2 py-1 rounded text-xs">${j.estatus}</span> `;
    
    if (infoExtra) {
        jugadores.innerHTML = `<div class="flex flex-wrap gap-2 justify-center">${infoExtra}</div>`;
    } else {
        jugadores.innerHTML = '<p class="text-slate-500 text-sm text-center py-4">Sin información adicional</p>';
    }
    
    modal.classList.remove('hidden');
}

function cerrarInfoEquipo() {
    const modal = document.getElementById('modalInfoEquipo');
    if (!modal) return;
    
    // Restaurar estructura original del modal
    const leftContent = modal.querySelector('.flex.items-center.gap-3');
    if (leftContent) {
        leftContent.innerHTML = `
            <img id="modalEqEscudo" src="" class="size-12 rounded-lg object-contain bg-white/10">
            <div>
                <h3 id="modalEqNombre" class="text-xl font-black text-white uppercase"></h3>
                <p id="modalEqPosicion" class="text-xs text-slate-400"></p>
            </div>
        `;
    }
    
    modal.classList.add('hidden');
}

function cerrarInfoEquipo() {
    document.getElementById('modalInfoEquipo').classList.add('hidden');
}