const escudoDefault = 'https://cdn-icons-png.flaticon.com/512/5323/5323982.png';

// Cache para evitar múltiples llamadas
let cacheData = null;
let cacheTime = 0;
const CACHE_DURATION = 5000; // 5 segundos

let datosGlobales = null; // Guardar datos para usar en modal

// Cargar datos al iniciar - UNA SOLA LLAMADA
document.addEventListener('DOMContentLoaded', async () => {
    const contenedorCarga = document.getElementById('contenedor-goleadores');
    if (contenedorCarga) {
        contenedorCarga.innerHTML = '<div class="col-span-full text-center py-8 text-slate-500">Cargando datos...</div>';
    }
    
    // UNA SOLA LLAMADA API para TODO (sin cache-busting para usar cache del servidor)
    const res = await fetch('/api/publico');
    if (!res.ok) throw new Error('Error al cargar datos: ' + res.status);
    datosGlobales = await res.json();
    
    // Debug: verificar datos recibidos
    console.log('Datos recibidos:', {
        jugadores: datosGlobales.jugadores ? Object.keys(datosGlobales.jugadores).length : 0,
        equipos: datosGlobales.equipos ? Object.keys(datosGlobales.equipos).length : 0,
        partidos: datosGlobales.partidos ? Object.keys(datosGlobales.partidos).length : 0
    });
    
// Renderizar cada sección
    cargarGoleadores(datosGlobales.jugadores, datosGlobales.equipos, datosGlobales.partidos);
    cargarJugadores(datosGlobales.jugadores, datosGlobales.partidos);
    cargarEquipos(datosGlobales.equipos);
    cargarPosiciones(datosGlobales.equipos, datosGlobales.partidos);
    cargarPorteros(datosGlobales.equipos, datosGlobales.partidos, datosGlobales.jugadores);
    cargarPartidos(datosGlobales.partidos);
    cargarLiguilla(datosGlobales.partidos);
    cargarRoles(datosGlobales.campos);
    
    // Renderizar botones de descarga por jornada
    renderizarBotonesDescargaJornadas();
    // El historial se carga solo al hacer click en la pestaña (lazy load)
});

// Función para cambiar pestañas
function switchTab(tabName) {
    document.querySelectorAll('.tab-content').forEach(el => el.classList.add('hidden'));
    document.querySelectorAll('.extra-section').forEach(el => el.classList.remove('visible'));
    document.querySelectorAll('.tab-btn').forEach(btn => {
        btn.classList.remove('bg-blue-600', 'text-white');
        btn.classList.add('bg-slate-900', 'text-slate-400');
    });
    
    const tabContent = document.getElementById('tab-' + tabName);
    if (tabContent) {
        tabContent.classList.remove('hidden');
    }
    
    const btn = document.querySelector(`button[onclick="switchTab('${tabName}')"]`);
    if (btn) {
        btn.classList.remove('bg-slate-900', 'text-slate-400');
        btn.classList.add('bg-blue-600', 'text-white');
    }
    
    // Cargar historial si es necesario
    if (tabName === 'historial') {
        cargarHistorial();
    }
}

// Función para cambiar secciones extra (próximos, reportes)
function switchSection(sectionName) {
    document.querySelectorAll('.tab-content').forEach(el => el.classList.add('hidden'));
    document.querySelectorAll('.extra-section').forEach(el => el.classList.remove('visible'));
    document.querySelectorAll('.tab-btn').forEach(btn => {
        btn.classList.remove('bg-blue-600', 'text-white');
        btn.classList.add('bg-slate-900', 'text-slate-400');
    });
    
    const section = document.getElementById('section-' + sectionName);
    if (section) {
        section.classList.add('visible');
    }
    
    const btn = document.querySelector(`button[onclick="switchSection('${sectionName}')"]`);
    if (btn) {
        btn.classList.remove('bg-slate-900', 'text-slate-400');
        btn.classList.add('bg-blue-600', 'text-white');
    }
}

async function cargarHistorial() {
    const contenedor = document.getElementById('contenedor-historial');
    if (!contenedor) return;
    
    try {
        const res = await fetch('/api/historial?_=' + Date.now());
        const historial = await res.json();
        
        if (!historial || Object.keys(historial).length === 0) {
            contenedor.innerHTML = '<div class="col-span-full text-center py-8 text-slate-500">No hay torneos en el historial aún.</div>';
            return;
        }
        
        // Guardar para usar en modal de detalles
        window.historialData = historial;
        
        const tournaments = Object.values(historial).reverse();
        
        contenedor.innerHTML = tournaments.map((t, index) => {
            const fechaRaw = t.fecha || t.fecha_finalizacion || null;
            const fechaStr = fechaRaw ? new Date(fechaRaw).toLocaleDateString('es-MX', { year: 'numeric', month: 'short', day: 'numeric' }) : 'Sin fecha';
            
            const champion = t.campeon || t.primer_lugar || 'Sin campeón';
            const numPartidos = t.resumen_partidos ? Object.keys(t.resumen_partidos).length : 0;
            
            return `
            <div onclick="verDetallesTorneoPublico(${index})" class="glass-card rounded-xl p-4 border border-amber-500/20 bg-gradient-to-b from-amber-500/10 to-transparent cursor-pointer hover:border-amber-500/50 transition-all">
                <div class="flex items-center justify-between mb-3">
                    <span class="text-[10px] text-amber-400 font-bold uppercase">${fechaStr}</span>
                    <span class="text-lg">🏆</span>
                </div>
                <div class="text-center mb-3">
                    <div class="text-lg font-black text-white uppercase">${champion}</div>
                    <div class="text-[10px] text-amber-500 font-bold uppercase">Campeón</div>
                </div>
                <div class="pt-3 border-t border-slate-700/50 flex justify-between items-center">
                    <span class="text-slate-500 text-[8px] font-bold uppercase">${numPartidos} Partidos</span>
                    <span class="text-amber-500 text-[8px] font-bold uppercase hover:text-white transition">Ver Detalles →</span>
                </div>
            </div>`;
        }).join('');
    } catch (e) {
        contenedor.innerHTML = '<div class="col-span-full text-center py-8 text-slate-500">Error al cargar historial.</div>';
    }
}

function verDetallesTorneoPublico(index) {
    let modal = document.getElementById('modalDetallesHistorial');
    if (!modal) {
        modal = crearModalHistorialPublico();
    }
    
    const historial = window.historialData;
    if (!historial) return;
    
    const tournaments = Object.values(historial).reverse();
    const t = tournaments[index];
    if (!t) return;
    
    // Header
    document.getElementById('historial-titulo').innerText = t.nombre_torneo || `Torneo ${new Date(t.fecha_finalizacion || t.fecha).getFullYear()}`;
    document.getElementById('historial-subtitulo').innerText = `CAMPEÓN: ${t.campeon || t.primer_lugar || 'Sin campeón'}`;
    
    // Tabla de posiciones
    const tablaBody = document.getElementById('historial-tabla-body');
    tablaBody.innerHTML = '';
    
    let tablaData = t.tabla_final || [];
    
    if (tablaData.length === 0 && t.resumen_partidos) {
        let stats = {};
        Object.values(t.resumen_partidos).forEach(p => {
            const loc = p.local || p.equipo_local;
            const vis = p.visitante || p.equipo_visitante;
            const gl = parseInt(p.goles_local || 0);
            const gv = parseInt(p.goles_visitante || 0);
            
            if (!stats[loc]) stats[loc] = { nombre: loc, pj: 0, pts: 0, gf: 0 };
            if (!stats[vis]) stats[vis] = { nombre: vis, pj: 0, pts: 0, gf: 0 };
            
            stats[loc].pj++; stats[vis].pj++;
            stats[loc].gf += gl; stats[vis].gf += gv;
            
            if (gl > gv) stats[loc].pts += 3;
            else if (gv > gl) stats[vis].pts += 3;
            else { stats[loc].pts += 1; stats[vis].pts += 1; }
        });
        tablaData = Object.values(stats).sort((a, b) => b.pts - a.pts);
    }
    
    tablaData.forEach(eq => {
        tablaBody.innerHTML += `
            <tr class="border-b border-slate-800/50 hover:bg-white/5 transition">
                <td class="px-4 py-3 font-bold text-white text-[11px] uppercase">${eq.nombre || eq.equipo}</td>
                <td class="px-4 py-3 text-center text-slate-400 font-bold">${eq.pj || 0}</td>
                <td class="px-4 py-3 text-center text-amber-500 font-black text-xs">${eq.pts || eq.puntos || 0}</td>
                <td class="px-4 py-3 text-center text-slate-400 font-bold">${eq.gf || eq.goles_favor || 0}</td>
            </tr>`;
    });
    
    // Partidos
    const partidosContenedor = document.getElementById('historial-partidos');
    partidosContenedor.innerHTML = '';
    const partidos = t.resumen_partidos || t.partidos || {};
    
    Object.values(partidos).forEach(p => {
        const local = p.local || p.equipo_local;
        const visitante = p.visitante || p.equipo_visitante;
        const gl = p.goles_local ?? 0;
        const gv = p.goles_visitante ?? 0;
        
        partidosContenedor.innerHTML += `
            <div class="flex items-center justify-between bg-slate-800/30 p-3 rounded-xl border border-slate-800 mb-2">
                <span class="text-slate-500 text-[8px] font-black uppercase w-16">${p.jornada || 'Match'}</span>
                <div class="flex-1 flex justify-center items-center gap-4">
                    <span class="text-white font-bold text-[10px] uppercase w-24 text-right">${local}</span>
                    <span class="bg-slate-900 px-3 py-1 rounded-lg text-amber-500 font-black italic text-xs min-w-[50px] text-center">${gl} - ${gv}</span>
                    <span class="text-white font-bold text-[10px] uppercase w-24 text-left">${visitante}</span>
                </div>
            </div>`;
    });
    
    // Goleadores del torneo
    const statsFinales = document.getElementById('historial-stats');
    statsFinales.innerHTML = '';
    
    let goleadoresTorneo = {};
    Object.values(partidos).forEach(partido => {
        const detalle = partido.detalle_jugadores || {};
        Object.entries(detalle).forEach(([tel, info]) => {
            if (info.goles > 0) {
                const nombreReal = info.nombre || tel;
                if (!goleadoresTorneo[tel]) {
                    goleadoresTorneo[tel] = { nombre: nombreReal, goles: 0 };
                }
                goleadoresTorneo[tel].goles += info.goles;
            }
        });
    });
    
    const rankingGoleadores = Object.values(goleadoresTorneo).sort((a, b) => b.goles - a.goles).slice(0, 6);
    
    if (rankingGoleadores.length > 0) {
        let htmlGoleadores = `<div class="col-span-full">
            <h4 class="text-amber-500 text-[8px] font-black uppercase tracking-[0.2em] mb-3 italic">Máximos Goleadores</h4>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">`;
        
        rankingGoleadores.forEach((jugador, i) => {
            const esPrimero = i === 0;
            htmlGoleadores += `
                <div class="flex justify-between items-center ${esPrimero ? 'bg-amber-500/10 border-amber-500/30' : 'bg-slate-800/40 border-slate-700/50'} p-3 rounded-xl border">
                    <div class="flex items-center gap-3">
                        <span class="text-lg">${i === 0 ? '🥇' : i === 1 ? '🥈' : i === 2 ? '🥉' : '⚽'}</span>
                        <span class="text-white font-black text-[11px] uppercase italic">${jugador.nombre}</span>
                    </div>
                    <span class="${esPrimero ? 'text-amber-500' : 'text-white'} font-black text-sm">${jugador.goles}</span>
                </div>`;
        });
        
        htmlGoleadores += `</div></div>`;
        statsFinales.innerHTML = htmlGoleadores;
    } else {
        statsFinales.innerHTML = '<p class="text-slate-500 text-[10px]">No hay datos de goleadores.</p>';
    }
    
    modal.classList.remove('hidden');
}

function crearModalHistorialPublico() {
    const modal = document.createElement('div');
    modal.id = 'modalDetallesHistorial';
    modal.className = 'fixed inset-0 z-50 hidden';
    modal.innerHTML = `
        <div class="absolute inset-0 bg-black/80 backdrop-blur-sm" onclick="cerrarModalHistorialPublico()"></div>
        <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-full max-w-2xl max-h-[90vh] bg-slate-900 border border-slate-700 rounded-2xl p-6 shadow-2xl overflow-y-auto">
            <div class="flex justify-between items-start mb-4">
                <div>
                    <h2 id="historial-titulo" class="text-2xl font-black text-white uppercase italic">Detalles del Torneo</h2>
                    <p id="historial-subtitulo" class="text-amber-500 text-[10px] font-bold uppercase tracking-widest mt-1"></p>
                </div>
                <button onclick="cerrarModalHistorialPublico()" class="text-slate-400 hover:text-white text-2xl">&times;</button>
            </div>
            <div class="mb-4">
                <h3 class="text-sm font-bold text-slate-400 uppercase mb-2">Tabla de Posiciones</h3>
                <div class="bg-slate-800/50 rounded-xl overflow-hidden">
                    <table class="w-full text-xs">
                        <thead class="bg-slate-800 text-slate-400 uppercase">
                            <tr>
                                <th class="px-4 py-2 text-left">Equipo</th>
                                <th class="px-4 py-2 text-center">PJ</th>
                                <th class="px-4 py-2 text-center">PTS</th>
                                <th class="px-4 py-2 text-center">GF</th>
                            </tr>
                        </thead>
                        <tbody id="historial-tabla-body" class="text-slate-300"></tbody>
                    </table>
                </div>
            </div>
            <div class="mb-4">
                <h3 class="text-sm font-bold text-slate-400 uppercase mb-2">Partidos</h3>
                <div id="historial-partidos" class="space-y-2 max-h-60 overflow-y-auto"></div>
            </div>
            <div>
                <div id="historial-stats"></div>
            </div>
        </div>
    `;
    document.body.appendChild(modal);
    return modal;
}

function cerrarModalHistorialPublico() {
    const modal = document.getElementById('modalDetallesHistorial');
    if (modal) modal.classList.add('hidden');
}

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
            <img src="${e.escudo || escudoDefault}" onerror="this.src='${escudoDefault}'" class="escudo-small" style="width:40px; height:40px;">
            <span class="text-xs font-bold text-white text-center uppercase">${e.nombre}</span>
        </div>
    `).join('');
}

// === RANKING COMPLETO DE JUGADORES (COMPETITIVO) ===
function cargarJugadores(jugadores, partidos) {
    const contenedor = document.getElementById('contenedor-jugadores');
    if (!contenedor) return;
    
    if (!jugadores || !partidos) {
        contenedor.innerHTML = '<div class="p-8 text-center text-slate-500">No hay datos disponibles.</div>';
        return;
    }

    // Calculate stats from fase regular only
    const scorerStats = {};
    const pjStats = {};
    
    // Primero intentar obtener stats de partidos confirmados (detalle_jugadores)
    let tieneStatsPartidos = false;
    Object.values(partidos || {}).forEach(p => {
        if (!p.resultado_confirmado) return;
        
        const jornada = String(p.jornada || '').toUpperCase();
        if (jornada === 'CUARTOS' || jornada === 'SEMIFINAL' || jornada === 'FINAL' || jornada === 'LIGUILLA') return;
        
        if (p.detalle_jugadores) {
            tieneStatsPartidos = true;
            Object.entries(p.detalle_jugadores).forEach(([tel, stats]) => {
                const jug = jugadores[tel];
                if (!scorerStats[tel]) {
                    scorerStats[tel] = { telefono: tel, nombre: jug?.nombre || tel, equipo: jug?.equipo || '', goles: 0 };
                }
                if (stats.asistio && stats.goles > 0) {
                    scorerStats[tel].goles += parseInt(stats.goles || 0);
                }
                if (stats.asistio) {
                    pjStats[tel] = (pjStats[tel] || 0) + 1;
                }
            });
        }
    });
    
    // Si no hay stats de partidos, usar los datos directos de jugadores
    if (!tieneStatsPartidos || Object.keys(scorerStats).length === 0) {
        Object.entries(jugadores || {}).forEach(([tel, j]) => {
            if ((j.goles || 0) > 0) {
                scorerStats[tel] = { 
                    telefono: tel, 
                    nombre: j.nombre || tel, 
                    equipo: j.equipo || '', 
                    goles: j.goles || 0,
                    pj: j.partidos_jugados || 0
                };
            }
        });
    }
    
    const faseRegular = Object.values(scorerStats)
        .filter(j => j.goles > 0)
        .sort((a, b) => b.goles - a.goles || (pjStats[a.telefono] || 999) - (pjStats[b.telefono] || 999));
    
    const total = Object.entries(jugadores || {})
        .filter(([tel, j]) => (j.goles || 0) > 0)
        .map(([tel, j]) => ({ telefono: tel, nombre: j.nombre, equipo: j.equipo, goles: j.goles, pj: j.partidos_jugados || 0 }))
        .sort((a, b) => b.goles - a.goles);
    
    // Use window to store current mode
    window.jugadoresMode = window.jugadoresMode || 'regular';
    
    const lista = window.jugadoresMode === 'regular' ? faseRegular : total;

    if (lista.length === 0) {
        contenedor.innerHTML = '<div class="p-8 text-center text-slate-500">No hay jugadores registrados con goles.</div>';
        return;
    }

    // Solo Top 10
    const top10 = lista.slice(0, 10);

    let html = `
        <div class="flex justify-end mb-2">
            <button onclick="toggleJugadoresMode()" class="text-xs px-2 py-1 rounded ${window.jugadoresMode === 'regular' ? 'bg-emerald-600 text-white' : 'bg-slate-700 text-slate-400'}">
                ${window.jugadoresMode === 'regular' ? 'Fase Regular' : 'Total'}
            </button>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-xs">
                <thead class="bg-slate-800/80 text-slate-400 uppercase tracking-wider text-[10px]">
                    <tr>
                        <th class="px-4 py-3 text-left">#</th>
                        <th class="px-4 py-3 text-left">Jugador</th>
                        <th class="px-4 py-3 text-center">Equipo</th>
                        <th class="px-4 py-3 text-center">PJ</th>
                        <th class="px-4 py-3 text-center text-emerald-400">Goles</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/50">
                    ${top10.map((j, i) => {
                        const rowClass = i < 3 ? 'bg-gradient-to-r from-amber-500/10 to-transparent' : 'hover:bg-slate-800/30';
                        const rankClass = i === 0 ? 'text-amber-400 font-black' :
                                        i === 1 ? 'text-slate-300 font-bold' :
                                        i === 2 ? 'text-orange-400 font-bold' : 'text-blue-400 font-bold';
                        const pj = window.jugadoresMode === 'regular' ? (pjStats[j.telefono] || 0) : (j.pj || 0);
                        
                        return `
                        <tr class="${rowClass} transition-colors cursor-pointer hover:bg-blue-500/20" onclick="abrirInfoJugador('${j.telefono}')">
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
                            <td class="px-4 py-3 text-center text-slate-400">${pj}</td>
                            <td class="px-4 py-3 text-center text-emerald-400 font-bold">${j.goles}</td>
                        </tr>`;
                    }).join('')}
                </tbody>
            </table>
        </div>
        <div class="p-4 text-center text-slate-500 text-xs border-t border-slate-800/50">
            Toca un jugador para ver sus estadísticas
        </div>
    `;

    contenedor.innerHTML = html;
}

window.toggleJugadoresMode = function() {
    window.jugadoresMode = window.jugadoresMode === 'regular' ? 'total' : 'regular';
    cargarJugadores(datosGlobales?.jugadores, datosGlobales?.partidos);
}

// Función para mostrar info de portero
function abrirInfoPortero(nombreEquipo) {
    const stats = window.porterosStats?.[nombreEquipo];
    if (!stats) return;
    
    const modal = document.getElementById('modalInfoPortero');
    if (modal) {
        document.getElementById('modalPorteroNombre').innerText = stats.portero_nombre;
        document.getElementById('modalPorteroEquipo').innerText = stats.nombre;
        document.getElementById('modalPorteroPJ').innerText = stats.pj;
        document.getElementById('modalPorteroGC').innerText = stats.gc;
        
        modal.classList.remove('hidden');
        modal.classList.add('flex');
    }
}

function cerrarModalPortero() {
    const modal = document.getElementById('modalInfoPortero');
    if (modal) {
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }
}

// TABLA DE GOLEADORES - TOP 3 HORIZONTAL + TOP 3 PORTEROS
function cargarGoleadores(jugadores, equipos, partidos) {
    const contenedor = document.getElementById('contenedor-goleadores');
    if (!contenedor) return;

    // Calculate team standings for tie-breaker
    const teamStats = {};
    for (const id in equipos) {
        teamStats[equipos[id].nombre] = { pj: 0, pts: 0, gf: 0, gc: 0 };
    }
    
    Object.values(partidos || {}).forEach(p => {
        if (p.resultado_confirmado) {
            const jornada = String(p.jornada || '').toUpperCase();
            if (jornada === 'CUARTOS' || jornada === 'SEMIFINAL' || jornada === 'FINAL' || jornada === 'LIGUILLA') return;
            
            const loc = p.equipo_local, vis = p.equipo_visitante;
            const gl = parseInt(p.goles_local || 0), gv = parseInt(p.goles_visitante || 0);
            
            if (teamStats[loc] && teamStats[vis]) {
                teamStats[loc].pj++; teamStats[vis].pj++;
                teamStats[loc].gf += gl; teamStats[loc].gc += gv;
                teamStats[vis].gf += gv; teamStats[vis].gc += gl;
                if (gl > gv) { teamStats[loc].pts += 3; teamStats[loc].g = (teamStats[loc].g || 0) + 1; }
                else if (gv > gl) { teamStats[vis].pts += 3; teamStats[vis].g = (teamStats[vis].g || 0) + 1; }
                else { teamStats[loc].pts++; teamStats[vis].pts++; }
            }
        }
    });
    
    const teamRank = Object.values(teamStats)
        .sort((a, b) => b.pts - a.pts || (b.gf - b.gc) - (a.gf - a.gc))
        .map((t, i) => ({ ...t, posicion: i + 1 }));
    
    // Calculate goals ONLY from regular phase matches
    const scorerStats = {};
    const pjStats = {};
    
    Object.values(partidos || {}).forEach(p => {
        if (!p.resultado_confirmado) return;
        
        const jornada = String(p.jornada || '').toUpperCase();
        if (jornada === 'CUARTOS' || jornada === 'SEMIFINAL' || jornada === 'FINAL' || jornada === 'LIGUILLA') return;
        
        const eqLocal = p.equipo_local;
        const eqVis = p.equipo_visitante;
        
        if (p.detalle_jugadores) {
            Object.entries(p.detalle_jugadores).forEach(([tel, stats]) => {
                if (stats.asistio && stats.goles > 0) {
                    const jug = jugadores[tel];
                    if (!scorerStats[tel]) {
                        scorerStats[tel] = { 
                            telefono: tel, 
                            nombre: jug?.nombre || tel, 
                            equipo: jug?.equipo || '',
                            goles: 0 
                        };
                    }
                    scorerStats[tel].goles += parseInt(stats.goles || 0);
                }
                if (stats.asistio) {
                    if (!pjStats[tel]) pjStats[tel] = 0;
                    pjStats[tel]++;
                }
            });
        }
    });
    
    // Sort by regular phase goals, then by better team position (tie-breaker)
    const goleadores = Object.values(scorerStats)
        .filter(j => j.goles > 0)
        .sort((a, b) => {
            if (b.goles !== a.goles) return b.goles - a.goles;
            // Tie-breaker: fewer matches played = better
            const pjA = pjStats[a.telefono] || 999;
            const pjB = pjStats[b.telefono] || 999;
            return pjA - pjB;
        })
        .slice(0, 3);

    // Calculate top 3 goalkeepers
    const porteroStats = {};
    for (const id in equipos) {
        const eq = equipos[id];
        porteroStats[eq.nombre] = { nombre: eq.nombre, portero_nombre: eq.nombre + ' (Portero)', pj: 0, gc: 0 };
    }
    
    Object.values(partidos || {}).forEach(partido => {
        if (partido.resultado_confirmado) {
            const jornada = String(partido.jornada || '').toUpperCase();
            if (jornada === 'CUARTOS' || jornada === 'SEMIFINAL' || jornada === 'FINAL' || jornada === 'LIGUILLA') return;
            
            const loc = partido.equipo_local;
            const vis = partido.equipo_visitante;
            const gl = parseInt(partido.goles_local || 0);
            const gv = parseInt(partido.goles_visitante || 0);
            
            if (porteroStats[loc] && porteroStats[vis]) {
                porteroStats[loc].pj++; porteroStats[loc].gc += gv;
                porteroStats[vis].pj++; porteroStats[vis].gc += gl;
            }
        }
    });
    
    // Get players map for portero names
    const playersMap = {};
    for (const tel in jugadores) {
        playersMap[tel] = jugadores[tel];
    }
    for (const id in equipos) {
        const eq = equipos[id];
        if (eq.portero_id && playersMap[eq.portero_id]) {
            porteroStats[eq.nombre].portero_nombre = playersMap[eq.portero_id].nombre;
        }
    }
    
    const porterosTop = Object.values(porteroStats)
        .filter(p => p.pj > 0)
        .sort((a, b) => {
            if (a.gc !== b.gc) return a.gc - b.gc;
            return b.pj - a.pj;
        })
        .slice(0, 3);

    if (goleadores.length === 0 && porterosTop.length === 0) {
        contenedor.innerHTML = '<div class="col-span-full text-center py-8 text-slate-500">Aún no hay estadísticas registradas.</div>';
        return;
    }

    // TOP 3 GOLEADORES + TOP 3 PORTEROS - Diseño ultra-compacto para móvil
    contenedor.innerHTML = `
        <div class="grid grid-cols-1 gap-4">
<!-- Top Goleadores -->
            <div>
                <h4 class="text-[10px] font-bold text-amber-400 uppercase mb-1 text-center">🏆 Top Goleadores (Fase Regular)</h4>
                <div class="flex gap-1 overflow-x-auto pb-1">
                    ${goleadores.length > 0 ? goleadores.map((j, i) => {
                        const medalEmoji = i === 0 ? '🥇' : i === 1 ? '🥈' : '🥉';
                        return `
                        <div class="flex-shrink-0" onclick="abrirInfoJugador('${j.telefono}')" style="cursor:pointer">
                            <div class="flex items-center gap-1.5 bg-slate-800/60 rounded-lg px-2 py-1 ${i === 0 ? 'border border-amber-500/30' : ''}">
                                <span class="text-sm">${medalEmoji}</span>
                                <div class="flex flex-col leading-tight">
                                    <span class="text-[10px] font-bold text-white truncate max-w-[60px]">${j.nombre}</span>
                                    <span class="text-[8px] text-blue-400">${j.equipo}</span>
                                </div>
                                <span class="text-xs font-black text-amber-400">${j.goles}</span>
                            </div>
                        </div>`;
                    }).join('') : '<span class="text-xs text-slate-500">-</span>'}
                </div>
            </div>
            <!-- Top Porteros - Guante de Oro -->
            <div>
                <h4 class="text-[10px] font-bold text-emerald-400 uppercase mb-1 text-center">🧤 Guante de Oro</h4>
                <div class="flex gap-1 overflow-x-auto pb-1">
                    ${porterosTop.length > 0 ? porterosTop.map((p, i) => {
                        const medalEmoji = i === 0 ? '🥇' : i === 1 ? '🥈' : '🥉';
                        return `
                        <div class="flex-shrink-0" onclick="abrirInfoPortero('${p.nombre}')" style="cursor:pointer">
                            <div class="flex items-center gap-1.5 bg-slate-800/60 rounded-lg px-2 py-1 ${i === 0 ? 'border border-emerald-500/30' : ''}">
                                <span class="text-sm">${medalEmoji}</span>
                                <div class="flex flex-col leading-tight">
                                    <span class="text-[10px] font-bold text-white truncate max-w-[60px]">${p.portero_nombre}</span>
                                    <span class="text-[8px] text-cyan-400">${p.nombre}</span>
                                </div>
                                <div class="flex items-center gap-1">
                                    <span class="text-[8px] text-slate-500">${p.pj}</span>
                                    <span class="text-[9px] font-bold ${p.gc <= 5 ? 'text-emerald-400' : p.gc <= 10 ? 'text-yellow-400' : 'text-rose-400'}">${p.gc}</span>
                                </div>
                            </div>
                        </div>`;
                    }).join('') : '<span class="text-xs text-slate-500">-</span>'}
                </div>
            </div>
        </div>`;
}

 // Tabla de Posiciones
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
            const jornada = partido.jornada;
            const esLiguilla = jornada && (
                String(jornada).toUpperCase() === 'CUARTOS' ||
                String(jornada).toUpperCase() === 'SEMIFINAL' ||
                String(jornada).toUpperCase() === 'FINAL' ||
                String(jornada).toUpperCase() === 'LIGUILLA'
            );
            
            if (esLiguilla) return;
            
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

    // Tabla de posiciones
    contenedor.innerHTML = `
        <div class="overflow-x-auto">
            <table class="w-full text-xs">
                <thead class="bg-slate-800/80 text-slate-400 uppercase tracking-wider text-[10px]">
                    <tr>
                        <th class="px-3 py-3 text-center">#</th>
                        <th class="px-3 py-3 text-left">Equipo</th>
                        <th class="px-3 py-3 text-center">PJ</th>
                        <th class="px-3 py-3 text-center">G</th>
                        <th class="px-3 py-3 text-center">E</th>
                        <th class="px-3 py-3 text-center">P</th>
                        <th class="px-3 py-3 text-center text-emerald-400 font-bold">PTS</th>
                        <th class="px-3 py-3 text-center">GF</th>
                        <th class="px-3 py-3 text-center">GC</th>
                        <th class="px-3 py-3 text-center">DG</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/50">
                    ${tabla.map((t, i) => {
                        const dg = t.gf - t.gc;
                        return `
                        <tr class="hover:bg-blue-500/10 cursor-pointer transition-colors" onclick="abrirInfoEquipo('${t.nombre.replace(/'/g, "\\'")}')">
                            <td class="px-3 py-3 text-center font-bold ${i < 3 ? 'text-amber-400' : 'text-slate-500'}">${i + 1}</td>
                            <td class="px-3 py-3">
                                <div class="flex items-center gap-2">
                                    <img src="${t.escudo || escudoDefault}" onerror="this.src='${escudoDefault}'" class="size-5 rounded object-contain">
                                    <span class="font-bold text-slate-200">${t.nombre}</span>
                                </div>
                            </td>
                            <td class="px-3 py-3 text-center text-slate-400">${t.pj}</td>
                            <td class="px-3 py-3 text-center text-slate-400">${t.g}</td>
                            <td class="px-3 py-3 text-center text-slate-400">${t.e}</td>
                            <td class="px-3 py-3 text-center text-slate-400">${t.p}</td>
                            <td class="px-3 py-3 text-center font-bold text-emerald-400">${t.pts}</td>
                            <td class="px-3 py-3 text-center text-slate-400">${t.gf}</td>
                            <td class="px-3 py-3 text-center text-slate-400">${t.gc}</td>
                            <td class="px-3 py-3 text-center font-bold ${dg >= 0 ? 'text-emerald-400' : 'text-red-400'}">${dg > 0 ? '+' + dg : dg}</td>
                        </tr>`;
                    }).join('')}
                </tbody>
            </table>
        </div>
        <div class="p-3 text-center text-slate-500 text-[10px] border-t border-slate-800/30">
            Toca un equipo para ver detalles
        </div>`;
}

// === CARGAR PORTEROS (GUANTE DE ORO) ===
function cargarPorteros(equipos, partidos, jugadores) {
    const contenedor = document.getElementById('contenedor-porteros');
    if (!contenedor) return;
    
    const playersMap = {};
    if (jugadores) {
        for (const telefono in jugadores) {
            playersMap[telefono] = jugadores[telefono];
        }
    }
    
    // Calculate fase regular stats
    const statsRegular = {};
    const statsTotal = {};
    
    for (const id in equipos) {
        const eq = equipos[id];
        const eqNombre = eq.nombre;
        
        let porteroNombre = eqNombre + ' (Portero)';
        if (eq.portero_id && playersMap[eq.portero_id]) {
            porteroNombre = playersMap[eq.portero_id].nombre;
        } else if (eq.portero_nombre) {
            porteroNombre = eq.portero_nombre;
        }
        
        statsRegular[eqNombre] = {
            nombre: eqNombre,
            escudo: eq.escudo || '',
            portero_nombre: porteroNombre,
            pj: 0, gc: 0
        };
        statsTotal[eqNombre] = {
            nombre: eqNombre,
            escudo: eq.escudo || '',
            portero_nombre: porteroNombre,
            pj: 0, gc: 0
        };
    }
    
    Object.values(partidos || {}).forEach(partido => {
        if (!partido.resultado_confirmado) return;
        
        const jornada = partido.jornada;
        const esLiguilla = jornada && (
            String(jornada).toUpperCase() === 'CUARTOS' ||
            String(jornada).toUpperCase() === 'SEMIFINAL' ||
            String(jornada).toUpperCase() === 'FINAL' ||
            String(jornada).toUpperCase() === 'LIGUILLA'
        );
        
        const loc = partido.equipo_local;
        const vis = partido.equipo_visitante;
        const gl = parseInt(partido.goles_local || 0);
        const gv = parseInt(partido.goles_visitante || 0);
        
        // Always add to total
        if (statsTotal[loc] && statsTotal[vis]) {
            statsTotal[loc].pj++; statsTotal[loc].gc += gv;
            statsTotal[vis].pj++; statsTotal[vis].gc += gl;
        }
        
        // Add to regular ONLY if not liguilla
        if (!esLiguilla) {
            if (statsRegular[loc] && statsRegular[vis]) {
                statsRegular[loc].pj++; statsRegular[loc].gc += gv;
                statsRegular[vis].pj++; statsRegular[vis].gc += gl;
            }
        }
    });

    const faseRegular = Object.values(statsRegular)
        .filter(t => t.pj > 0)
        .sort((a, b) => {
            if (a.gc !== b.gc) return a.gc - b.gc;
            return b.pj - a.pj;
        });
    
    const total = Object.values(statsTotal)
        .filter(t => t.pj > 0)
        .sort((a, b) => {
            if (a.gc !== b.gc) return a.gc - b.gc;
            return b.pj - a.pj;
        });

    window.porterosMode = window.porterosMode || 'regular';
    const porteros = window.porterosMode === 'regular' ? faseRegular : total;

    if (porteros.length === 0) {
        contenedor.innerHTML = '<div class="p-8 text-center text-slate-500">No hay porteros con partidos jugados.</div>';
        return;
    }

    const top10 = porteros.slice(0, 10);

    contenedor.innerHTML = `
        <div class="flex justify-end mb-2">
            <button onclick="togglePorterosMode()" class="text-xs px-2 py-1 rounded ${window.porterosMode === 'regular' ? 'bg-emerald-600 text-white' : 'bg-slate-700 text-slate-400'}">
                ${window.porterosMode === 'regular' ? 'Fase Regular' : 'Total'}
            </button>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-xs">
                <thead class="bg-slate-800/80 text-slate-400 uppercase tracking-wider text-[10px]">
                    <tr>
                        <th class="px-3 py-3 text-center">#</th>
                        <th class="px-3 py-3 text-left">🧤 Portero</th>
                        <th class="px-3 py-3 text-left">Equipo</th>
                        <th class="px-3 py-3 text-center">PJ</th>
                        <th class="px-3 py-3 text-center text-rose-400">GC</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/50">
                    ${top10.map((t, i) => {
                        const rowClass = i < 3 ? 'bg-gradient-to-r from-green-500/10 to-transparent cursor-pointer hover:bg-slate-800/30' : 'cursor-pointer hover:bg-slate-800/30';
                        const rankClass = i === 0 ? 'text-green-400 font-black' :
                                         i === 1 ? 'text-slate-300 font-bold' :
                                         i === 2 ? 'text-yellow-400 font-bold' : 'text-blue-400 font-bold';
                        const gcClass = t.gc <= 5 ? 'text-green-400' : t.gc <= 10 ? 'text-yellow-400' : 'text-rose-400';
                        
                        return `
                        <tr class="${rowClass} transition-colors" onclick="abrirInfoPortero('${t.nombre}')">
                            <td class="px-3 py-3 ${rankClass}">${i + 1}</td>
                            <td class="px-3 py-3 font-bold text-white">${t.portero_nombre}</td>
                            <td class="px-3 py-3">
                                <span class="px-2 py-1 rounded-full bg-slate-800 text-slate-400 text-[10px] font-bold">${t.nombre}</span>
                            </td>
                            <td class="px-3 py-3 text-center text-slate-400">${t.pj}</td>
                            <td class="px-3 py-3 text-center font-bold ${gcClass}">${t.gc}</td>
                        </tr>`;
                    }).join('')}
                </tbody>
            </table>
        </div>
        <div class="p-4 text-center text-slate-500 text-xs border-t border-slate-800/50">
            Guante de Oro 🧤 - Menos GC = Mejor
        </div>`;
    
    window.porterosStats = window.porterosMode === 'regular' ? statsRegular : statsTotal;
}

window.togglePorterosMode = function() {
    window.porterosMode = window.porterosMode === 'regular' ? 'total' : 'regular';
    cargarPorteros(datosGlobales?.equipos, datosGlobales?.partidos, datosGlobales?.jugadores);
}

// Función para abrir modal de equipo
function abrirInfoEquipo(nombreEquipo) {
    const eq = Object.values(datosGlobales.equipos || {}).find(e => e.nombre === nombreEquipo);
    if (!eq) return;
    
    const modal = document.getElementById('modalInfoEquipo');
    if (!modal) return;
    
    // Calcular stats
    const statsEq = { pj: 0, g: 0, e: 0, p: 0, gf: 0, gc: 0, pts: 0, ultimos: [] };
    
    Object.values(datosGlobales.partidos || {}).forEach(p => {
        if (p.resultado_confirmado && (p.equipo_local === nombreEquipo || p.equipo_visitante === nombreEquipo)) {
            const esLocal = p.equipo_local === nombreEquipo;
            const gl = parseInt(p.goles_local || 0);
            const gv = parseInt(p.goles_visitante || 0);
            const miGoles = esLocal ? gl : gv;
            const rivalGoles = esLocal ? gv : gl;
            let resultado = '';
            if (miGoles > rivalGoles) { resultado = 'G'; statsEq.g++; statsEq.pts += 3; }
            else if (miGoles < rivalGoles) { resultado = 'P'; statsEq.p++; }
            else { resultado = 'E'; statsEq.e++; statsEq.pts++; }
            statsEq.pj++; statsEq.gf += miGoles; statsEq.gc += rivalGoles;
            statsEq.ultimos.push(resultado);
        }
    });
    
    // Calcular posición
    const equipos = datosGlobales.equipos || {};
    const tablaStats = {};
    for (const id in equipos) {
        tablaStats[equipos[id].nombre] = { nombre: equipos[id].nombre, pts: 0 };
    }
    Object.values(datosGlobales.partidos || {}).forEach(p => {
        if (p.resultado_confirmado && tablaStats[p.equipo_local] && tablaStats[p.equipo_visitante]) {
            const gl = parseInt(p.goles_local || 0);
            const gv = parseInt(p.goles_visitante || 0);
            if (gl > gv) tablaStats[p.equipo_local].pts += 3;
            else if (gl < gv) tablaStats[p.equipo_visitante].pts += 3;
            else { tablaStats[p.equipo_local].pts++; tablaStats[p.equipo_visitante].pts++; }
        }
    });
    const tablaOrdenada = Object.values(tablaStats).sort((a, b) => b.pts - a.pts);
    const posicion = tablaOrdenada.findIndex(t => t.nombre === nombreEquipo) + 1;
    
    // Renderizar modal
    const leftContent = modal.querySelector('.flex.items-center.gap-3');
    if (leftContent) {
        leftContent.innerHTML = `
            <img src="${eq.escudo || escudoDefault}" onerror="this.src='${escudoDefault}'" class="size-14 rounded-lg object-contain bg-white/10">
            <div>
                <h3 class="text-lg font-black text-white uppercase">${nombreEquipo}</h3>
                <p class="text-xs text-blue-400 font-bold">Posición #${posicion}</p>
            </div>
        `;
    }
    
    const stats = document.getElementById('modalEqStats');
    const jugadores = document.getElementById('modalEqJugadores');
    
    stats.innerHTML = `
        <div class="bg-slate-800/50 rounded-lg p-2 text-center"><div class="text-[9px] text-slate-500 uppercase">PJ</div><div class="text-lg font-black text-white">${statsEq.pj}</div></div>
        <div class="bg-slate-800/50 rounded-lg p-2 text-center"><div class="text-[9px] text-slate-500 uppercase">G</div><div class="text-lg font-black text-emerald-400">${statsEq.g}</div></div>
        <div class="bg-slate-800/50 rounded-lg p-2 text-center"><div class="text-[9px] text-slate-500 uppercase">PTS</div><div class="text-lg font-black text-blue-400">${statsEq.pts}</div></div>
        <div class="bg-slate-800/50 rounded-lg p-2 text-center"><div class="text-[9px] text-slate-500 uppercase">DG</div><div class="text-lg font-black ${statsEq.gf - statsEq.gc >= 0 ? 'text-emerald-400' : 'text-red-400'}">${statsEq.gf - statsEq.gc}</div></div>
    `;
    
    // Últimos 5 partidos
    const ultimos5 = statsEq.ultimos.slice(-5).reverse();
    const ultimosHTML = ultimos5.map(r => {
        const color = r === 'G' ? 'bg-emerald-500' : r === 'E' ? 'bg-amber-500' : 'bg-red-500';
        return `<span class="size-6 rounded ${color} flex items-center justify-center text-[10px] font-black text-white">${r}</span>`;
    }).join('');
    
    // Jugadores del equipo
    const jugadoresEq = Object.values(datosGlobales.jugadores || {})
        .filter(j => j.equipo === nombreEquipo)
        .sort((a, b) => (b.goles || 0) - (a.goles || 0))
        .slice(0, 5);
    
    const jugadoresHTML = jugadoresEq.length > 0 
        ? jugadoresEq.map(j => `<div class="flex items-center justify-between bg-slate-800/30 rounded px-2 py-1"><span class="text-xs text-white">${j.nombre}</span><span class="text-xs font-bold text-emerald-400">${j.goles || 0}</span></div>`).join('')
        : '<p class="text-slate-500 text-xs text-center">Sin jugadores</p>';
    
    jugadores.innerHTML = `
        <div class="mb-3">
            <div class="text-[9px] text-slate-500 uppercase mb-2">Últimos 5 partidos</div>
            <div class="flex gap-1 justify-center">${ultimosHTML || '<span class="text-slate-500 text-xs">Sin partidos</span>'}</div>
        </div>
        <div>
            <div class="text-[9px] text-slate-500 uppercase mb-2">Top Goleadores</div>
            <div class="space-y-1">${jugadoresHTML}</div>
        </div>
    `;
    
    modal.classList.remove('hidden');
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
        });

    if (liguilla.length === 0) {
        contenedor.innerHTML = '<div class="col-span-full text-center py-8 text-slate-500">Aún no hay fase de liguilla o play-offs.</div>';
        return;
    }

    // Agrupar por fase
    const fases = {};
    liguilla.forEach(p => {
        let key = p.fase || p.jornada;
        if (!key) key = 'Eliminatorias';
        if (!fases[key]) fases[key] = [];
        fases[key].push(p);
    });

    // Orden de fases
    const ordenFases = {'Cuartos': 1, 'Semifinal': 2, 'Final': 3, 'Tercer Lugar': 4};
    const fasesOrdenadas = Object.entries(fases).sort((a, b) => {
        return (ordenFases[a[0]] || 99) - (ordenFases[b[0]] || 99);
    });

    // Guardar todos los partidos para buscarlos después (preservando el array completo)
    window.liguillaPartidos = [...liguilla];

    // Crear mapa para buscar por índice
    window.liguillaIndexMap = {};
    liguilla.forEach((p, idx) => {
        window.liguillaIndexMap[p.id] = idx;
    });

    contenedor.innerHTML = fasesOrdenadas.map(([fase, matches]) => {
        const esFinal = fase === 'Final';
        const faseClass = esFinal 
            ? 'from-purple-500/30 to-blue-500/30 border-purple-500/30' 
            : 'from-amber-500/20 to-orange-600/10 border-amber-500/20';
        const badge = esFinal ? '🏆' : 'Jornada';
        
        return `
        <div class="glass-card rounded-xl p-4 border ${faseClass}">
            <h3 class="text-sm font-bold ${esFinal ? 'text-purple-400' : 'text-amber-400'} uppercase mb-4 text-center flex items-center justify-center gap-2">
                ${badge} ${fase}
            </h3>
            <div class="space-y-3">
                ${matches.map((p) => {
                    const confirmado = p.resultado_confirmado;
                    const scoreClass = confirmado ? 'bg-emerald-500/20 text-emerald-400' : 'bg-slate-900 text-slate-500';
                    const idx = window.liguillaIndexMap[p.id];
                    
                    return `
                    <div class="flex items-center justify-between p-3 bg-slate-800/50 rounded-lg border border-slate-700/50 ${confirmado ? 'border-l-4 border-l-emerald-500' : ''} cursor-pointer hover:bg-slate-700/50 transition" onclick="abrirDetallePartidoLiguilla('${p.id}')">
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

// Función para abrir detalle de partido en liguilla
function abrirDetallePartidoLiguilla(partidoId) {
    // Buscar el partido por ID
    const idx = window.liguillaIndexMap?.[partidoId];
    const partido = window.liguillaPartidos?.[idx];
    if (!partido) return;
    
    const modal = document.getElementById('modalInfoEquipo');
    if (!modal) return;
    
    // Obtener info de goleadores separada por equipo
    // y lista de participantes desde detalle_jugadores (contiene 'asistio')
    let goleadoresLocalHTML = '<p class="text-slate-500 text-xs text-center">Sin goleadores</p>';
    let goleadoresVisitanteHTML = '<p class="text-slate-500 text-xs text-center">Sin goleadores</p>';
    let participantesLocalHTML = '<p class="text-slate-500 text-xs text-center">Sin datos</p>';
    let participantesVisitanteHTML = '<p class="text-slate-500 text-xs text-center">Sin datos</p>';
    
    if (partido.detalle_jugadores) {
        const goleadoresLocal = [];
        const goleadoresVisitante = [];
        const participantesLocal = [];
        const participantesVisitante = [];
        
        Object.entries(partido.detalle_jugadores).forEach(([tel, data]) => {
            // data viene directamente de Firebase, tiene 'asistio', 'goles', etc.
            const jugador = datosGlobales.jugadores?.[tel];
            const nombre = jugador?.nombre || tel;
            const dorsal = jugador?.numero || '#';
            const equipoJugador = jugador?.equipo || '';
            const esLocal = equipoJugador === partido.equipo_local;
            const esVisitante = equipoJugador === partido.equipo_visitante;
            
            // Participantes (los que asistieron)
            if (data.asistio === true) {
                if (esLocal) {
                    participantesLocal.push({ nombre, dorsal });
                } else if (esVisitante) {
                    participantesVisitante.push({ nombre, dorsal });
                } else {
                    participantesLocal.push({ nombre, dorsal });
                }
            }
            
            // Goleadores
            if (data.goles > 0) {
                if (esLocal) {
                    goleadoresLocal.push({ nombre, goles: data.goles });
                } else if (esVisitante) {
                    goleadoresVisitante.push({ nombre, goles: data.goles });
                } else {
                    goleadoresLocal.push({ nombre, goles: data.goles });
                }
            }
        });
        
        if (goleadoresLocal.length > 0) {
            goleadoresLocalHTML = goleadoresLocal.map(g => `
                <div class="flex items-center justify-between bg-slate-800/30 rounded px-2 py-1">
                    <span class="text-xs text-white">${g.nombre}</span>
                    <span class="text-xs font-bold text-emerald-400">${g.goles}</span>
                </div>
            `).join('');
        }
        
        if (goleadoresVisitante.length > 0) {
            goleadoresVisitanteHTML = goleadoresVisitante.map(g => `
                <div class="flex items-center justify-between bg-slate-800/30 rounded px-2 py-1">
                    <span class="text-xs text-white">${g.nombre}</span>
                    <span class="text-xs font-bold text-emerald-400">${g.goles}</span>
                </div>
            `).join('');
        }
        
        if (participantesLocal.length > 0) {
            participantesLocalHTML = participantesLocal.map(p => `
                <div class="text-xs text-slate-300 py-1 border-b border-slate-700/50 last:border-0">${p.dorsal} - ${p.nombre}</div>
            `).join('');
        }
        
        if (participantesVisitante.length > 0) {
            participantesVisitanteHTML = participantesVisitante.map(p => `
                <div class="text-xs text-slate-300 py-1 border-b border-slate-700/50 last:border-0">${p.dorsal} - ${p.nombre}</div>
            `).join('');
        }
    }
    
    // Info del partido
    const fecha = partido.fecha || 'Sin fecha';
    const hora = partido.hora || 'Sin hora';
    const campoId = partido.campo_id;
    let campo = 'Sin sede';
    if (campoId && datosGlobales.campos) {
        const campoObj = Object.values(datosGlobales.campos).find(c => c.id === campoId || c.lugar === campoId);
        if (campoObj) campo = campoObj.nombre || campoObj.lugar || 'Sin sede';
    }
    const jornada = partido.jornada || partido.fase || '-';
    
    // Estado del partido
    let estado = 'Programado';
    let estadoColor = 'bg-slate-500';
    if (partido.resultado_confirmado) {
        estado = 'Finalizado';
        estadoColor = 'bg-emerald-500';
    } else if (partido.estatus === 'en_juego' || partido.estatus === 'en vivo') {
        estado = 'En Vivo';
        estadoColor = 'bg-red-500 animate-pulse';
    }
    
    // Renderizar modal
    const leftContent = modal.querySelector('.flex.items-center.gap-3');
    if (leftContent) {
        const gl = parseInt(partido.goles_local || 0);
        const gv = parseInt(partido.goles_visitante || 0);
        const resultado = partido.resultado_confirmado ? `${gl} - ${gv}` : 'vs';
        
        leftContent.innerHTML = `
            <div class="text-center">
                <div class="text-xs text-slate-400 uppercase mb-1">${partido.equipo_local}</div>
                <div class="text-3xl font-black text-white">${resultado}</div>
                <div class="text-xs text-slate-400 uppercase mt-1">${partido.equipo_visitante}</div>
            </div>
        `;
    }
    
    const stats = document.getElementById('modalEqStats');
    const jugadores = document.getElementById('modalEqJugadores');
    
    stats.innerHTML = `
        <div class="bg-slate-800/50 rounded-lg p-2 text-center"><div class="text-[9px] text-slate-500 uppercase">Fecha</div><div class="text-sm font-bold text-white">${fecha}</div></div>
        <div class="bg-slate-800/50 rounded-lg p-2 text-center"><div class="text-[9px] text-slate-500 uppercase">Hora</div><div class="text-sm font-bold text-white">${hora}</div></div>
        <div class="bg-slate-800/50 rounded-lg p-2 text-center"><div class="text-[9px] text-slate-500 uppercase">Sede</div><div class="text-sm font-bold text-white">${campo}</div></div>
        <div class="${estadoColor} rounded-lg p-2 text-center"><div class="text-[9px] text-white uppercase font-bold">${estado}</div></div>
    `;
    
    jugadores.innerHTML = `
        <div class="mb-3">
            <div class="text-[9px] text-slate-500 uppercase mb-2">Fase: ${jornada}</div>
        </div>
        <div class="mb-3">
            <div class="flex gap-2 border-b border-slate-700 mb-3">
                <button onclick="togglePartyTabs('goleadores', this)" class="px-3 py-1 text-[10px] font-bold uppercase text-blue-400 border-b-2 border-blue-400">Goleadores</button>
                <button onclick="togglePartyTabs('participantes', this)" class="px-3 py-1 text-[10px] font-bold uppercase text-slate-500 border-b-2 border-transparent">Participantes</button>
            </div>
            <div id="tab-goleadores">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <div class="text-[9px] text-blue-400 uppercase mb-2 font-bold">${partido.equipo_local}</div>
                        <div class="space-y-1">${goleadoresLocalHTML}</div>
                    </div>
                    <div>
                        <div class="text-[9px] text-amber-400 uppercase mb-2 font-bold">${partido.equipo_visitante}</div>
                        <div class="space-y-1">${goleadoresVisitanteHTML}</div>
                    </div>
                </div>
            </div>
            <div id="tab-participantes" class="hidden">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <div class="text-[9px] text-blue-400 uppercase mb-2 font-bold">${partido.equipo_local}</div>
                        <div class="max-h-40 overflow-y-auto">${participantesLocalHTML}</div>
                    </div>
                    <div>
                        <div class="text-[9px] text-amber-400 uppercase mb-2 font-bold">${partido.equipo_visitante}</div>
                        <div class="max-h-40 overflow-y-auto">${participantesVisitanteHTML}</div>
                    </div>
                </div>
            </div>
        </div>
    `;
    
    modal.classList.remove('hidden');
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
    
    const stats = document.getElementById('modalEqStats');
    const jugadores = document.getElementById('modalEqJugadores');
    if (!stats || !jugadores) return;
    
    const dorsal = j.numero || 0;
    const pj = j.partidos_jugados || 0;
    const goles = j.goles || 0;
    const promedioGoles = pj > 0 ? (goles / pj).toFixed(2) : '0.00';
    const posicionEnRanking = obtenerPosicionRanking(telefono);
    const equipo = j.equipo || 'Sin equipo';
    
    // Reemplazar contenido del modal
    const leftContent = modal.querySelector('.flex.items-center.gap-3');
    if (leftContent) {
        leftContent.innerHTML = `
            <div class="size-16 rounded-full bg-gradient-to-br from-blue-500 to-blue-700 flex items-center justify-center shadow-lg shadow-blue-500/30">
                <span class="text-2xl font-black text-white">${dorsal}</span>
            </div>
            <div>
                <h3 id="modalEqNombre" class="text-xl font-black text-white uppercase">${j.nombre || 'Jugador'}</h3>
                <p id="modalEqPosicion" class="text-xs text-blue-400 font-bold">${equipo}</p>
            </div>
        `;
    }
    
    // Stats con estilo mejorado
    stats.innerHTML = `
        <div class="bg-gradient-to-br from-emerald-500/20 to-emerald-600/10 rounded-xl p-3 border border-emerald-500/20">
            <div class="text-[10px] text-emerald-400 uppercase font-bold">GOLES</div>
            <div class="text-2xl font-black text-white">${goles}</div>
        </div>
        <div class="bg-gradient-to-br from-blue-500/20 to-blue-600/10 rounded-xl p-3 border border-blue-500/20">
            <div class="text-[10px] text-blue-400 uppercase font-bold">PARTIDOS</div>
            <div class="text-2xl font-black text-white">${pj}</div>
        </div>
        <div class="bg-gradient-to-br from-amber-500/20 to-amber-600/10 rounded-xl p-3 border border-amber-500/20">
            <div class="text-[10px] text-amber-400 uppercase font-bold">RATIO</div>
            <div class="text-2xl font-black text-white">${promedioGoles}</div>
            <div class="text-[8px] text-amber-500">gol/partido</div>
        </div>
        <div class="bg-gradient-to-br from-purple-500/20 to-purple-600/10 rounded-xl p-3 border border-purple-500/20">
            <div class="text-[10px] text-purple-400 uppercase font-bold">RANKING</div>
            <div class="text-2xl font-black text-white">#${posicionEnRanking}</div>
        </div>
    `;
    
    // Información adicional
    let infoExtra = '';
    if (j.edad) {
        infoExtra += `
            <div class="bg-slate-800/50 rounded-lg p-3 flex flex-col items-center">
                <span class="text-xs text-slate-500 uppercase">Edad</span>
                <span class="text-lg font-black text-white"> ${j.edad}</span>
            </div>
        `;
    }
    infoExtra += `
        <div class="bg-slate-800/50 rounded-lg p-3 flex flex-col items-center">
            <span class="text-xs text-slate-500 uppercase">Dorsal</span>
            <span class="text-lg font-black text-blue-400">#${dorsal}</span>
        </div>
    `;
    if (j.estatus && j.estatus !== 'activo') {
        const estatusIcon = j.estatus === 'lesionado' ? '🏥' : '🚫';
        const estatusColor = j.estatus === 'lesionado' ? 'bg-orange-500' : 'bg-red-500';
        infoExtra += `
            <div class="${estatusColor} rounded-lg p-3 flex flex-col items-center">
                <span class="text-xs text-white uppercase font-bold">${estatusIcon} ${j.estatus}</span>
            </div>
        `;
    }
    
    // Agregar sección de "Análisis" fake
    const analisisIA = generarAnalisisIA(j, posicionEnRanking);
    
    jugadores.innerHTML = `
        <div class="grid grid-cols-3 gap-2 mb-4">${infoExtra}</div>
        <div class="bg-gradient-to-r from-indigo-500/10 to-purple-500/10 rounded-xl p-4 border border-indigo-500/20">
            <div class="flex items-center gap-2 mb-2">
                <span class="text-lg">🤖</span>
                <span class="text-sm font-black text-indigo-400 uppercase">AI Analysis</span>
            </div>
            <p class="text-xs text-slate-300 leading-relaxed">${analisisIA}</p>
        </div>
    `;
    
    modal.classList.remove('hidden');
}

function obtenerPosicionRanking(telefono) {
    const lista = Object.entries(datosGlobales?.jugadores || {})
        .sort((a, b) => (b[1].goles || 0) - (a[1].goles || 0));
    const pos = lista.findIndex(([tel]) => tel === telefono);
    return pos + 1;
}

function generarAnalisisIA(j, posicion) {
    const pj = j.partidos_jugados || 0;
    const goles = j.goles || 0;
    const equipo = j.equipo || 'Sin equipo';
    
    let nivel = ' PRINCIPIANTE';
    let icono = '🌱';
    
    if (posicion <= 3) {
        nivel = ' ELITE';
        icono = '🔥';
    } else if (posicion <= 10) {
        nivel = ' ESTRELLA';
        icono = '⭐';
    } else if (pj >= 10 && (goles / pj) >= 0.5) {
        nivel = ' GOLEADOR';
        icono = '⚽';
    } else if (pj >= 5) {
        nivel = ' TITULAR';
        icono = '🎯';
    }
    
    return `${icono} ${j.nombre || 'Jugador'} se encuentra en el ranking TOP #${posicion} con ${goles} goles en ${pj} partidos. Su rendimiento con ${equipo} es de ${(goles/pj || 0).toFixed(2)} goles por partido.${nivel === ' ELITE' ? ' ¡Jugador determinante!' : ''}`;
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

function togglePartyTabs(tabName, btn) {
    document.querySelectorAll('#modalEqJugadores .border-b button').forEach(b => {
        b.classList.remove('text-blue-400', 'border-blue-400');
        b.classList.add('text-slate-500', 'border-transparent');
    });
    btn.classList.remove('text-slate-500', 'border-transparent');
    btn.classList.add('text-blue-400', 'border-blue-400');
    
    if (tabName === 'goleadores') {
        document.getElementById('tab-goleadores').classList.remove('hidden');
        document.getElementById('tab-participantes').classList.add('hidden');
    } else {
        document.getElementById('tab-goleadores').classList.add('hidden');
        document.getElementById('tab-participantes').classList.remove('hidden');
    }
}

// === DESCARGAR PDF POR JORNADA ===
window.descargarPDFJornada = async function(jornada) {
    if (!datosGlobales) {
        alert('Error: No hay datos cargados');
        return;
    }
    
    const partidos = datosGlobales.partidos || {};
    const jugadores = datosGlobales.jugadores || {};
    const equipos = datosGlobales.equipos || {};
    
    // Determinar si es jornada numérica o fase de liguilla
    const esJornadaNumerica = !isNaN(parseInt(jornada));
    const jornadaStr = String(jornada).trim().toUpperCase();
    
    let partidosJornada, partidosAcumulados;
    
    if (esJornadaNumerica) {
        // Jornada numérica: filtrar por número
        const numJornada = parseInt(jornada);
        partidosJornada = Object.values(partidos).filter(p => 
            parseInt(p.jornada) === numJornada && p.resultado_confirmado
        );
        
        // Acumulativo hasta esa jornada
        partidosAcumulados = Object.values(partidos).filter(p => {
            const pj = parseInt(p.jornada);
            return !isNaN(pj) && pj <= numJornada && p.resultado_confirmado;
        });
    } else {
        // Fase de liguilla (CUARTOS, SEMIFINAL, FINAL) - filtrar por string
        partidosJornada = Object.values(partidos).filter(p => 
            String(p.jornada || '').trim().toUpperCase() === jornadaStr && p.resultado_confirmado
        );
        // Para liguilla no hay acumulado, solo esa fase
        partidosAcumulados = partidosJornada;
    }
    
    if (partidosJornada.length === 0) {
        alert('No hay partidos jugados en ' + jornada);
        return;
    }
    
    const fecha = new Date().toLocaleDateString('es-MX', { year: 'numeric', month: 'long', day: 'numeric' });
    
    // Calcular estadísticas según el tipo
    const stats = { goleadores: {}, equipos: {} };
    
    // Primero inicializar todos los equipos del catálogo
    const nombreEquiposMap = {};
    Object.values(equipos).forEach(eq => {
        const nombreNormalizado = (eq.nombre || '').trim();
        stats.equipos[nombreNormalizado] = { nombre: nombreNormalizado, gf: 0, gc: 0, pj: 0, g: 0, e: 0, p: 0, pts: 0 };
        nombreEquiposMap[nombreNormalizado.toLowerCase()] = nombreNormalizado;
    });
    
    // Calcular stats - para liguilla solo usar partidos de esa fase, no acumulados
    const partidosParaEstadisticas = esJornadaNumerica ? partidosAcumulados : partidosJornada;
    
    partidosParaEstadisticas.forEach(p => {
        // Goleadores - usar el ID del jugador (tel) como clave única para evitar duplicados
        if (p.detalle_jugadores) {
            Object.entries(p.detalle_jugadores).forEach(([tel, data]) => {
                if (data.asistio && data.goles > 0) {
                    const nombre = jugadores[tel]?.nombre || tel;
                    const equipo = jugadores[tel]?.equipo || '';
                    const dorsal = jugadores[tel]?.numero || 0;
                    // Usar tel como clave única para evitar duplicados por nombre
                    if (!stats.goleadores[tel]) {
                        stats.goleadores[tel] = { key: tel, nombre: nombre, dorsal: dorsal, equipo: equipo, goles: 0 };
                    }
                    stats.goleadores[tel].goles += parseInt(data.goles || 0);
                }
            });
        }
        
        // Equipos (acumulativo) - normalizar nombres
        const localNorm = (p.equipo_local || '').trim();
        const visitanteNorm = (p.equipo_visitante || '').trim();
        const localNombre = nombreEquiposMap[localNorm.toLowerCase()] || localNorm;
        const visitanteNombre = nombreEquiposMap[visitanteNorm.toLowerCase()] || visitanteNorm;
        
        if (localNombre && stats.equipos[localNombre]) {
            stats.equipos[localNombre].gf += parseInt(p.goles_local || 0);
            stats.equipos[localNombre].gc += parseInt(p.goles_visitante || 0);
            stats.equipos[localNombre].pj++;
            const gl = parseInt(p.goles_local || 0), gv = parseInt(p.goles_visitante || 0);
            if (gl > gv) { stats.equipos[localNombre].g++; stats.equipos[localNombre].pts += 3; }
            else if (gl < gv) { stats.equipos[localNombre].p++; }
            else { stats.equipos[localNombre].e++; stats.equipos[localNombre].pts += 1; }
        }
        if (visitanteNombre && stats.equipos[visitanteNombre]) {
            stats.equipos[visitanteNombre].gf += parseInt(p.goles_visitante || 0);
            stats.equipos[visitanteNombre].gc += parseInt(p.goles_local || 0);
            stats.equipos[visitanteNombre].pj++;
            const gl = parseInt(p.goles_local || 0), gv = parseInt(p.goles_visitante || 0);
            if (gv > gl) { stats.equipos[visitanteNombre].g++; stats.equipos[visitanteNombre].pts += 3; }
            else if (gv < gl) { stats.equipos[visitanteNombre].p++; }
            else { stats.equipos[visitanteNombre].e++; stats.equipos[visitanteNombre].pts += 1; }
        }
    });
    
    // Top goleadores - Top 10 con empates en la posición 10
    const goleadoresOrdenados = Object.values(stats.goleadores || {}).sort((a, b) => b.goles - a.goles);
    let topGoleadores = [];
    if (goleadoresOrdenados.length > 0) {
        // Tomar los primeros 10
        topGoleadores = goleadoresOrdenados.slice(0, 10);
        // Si hay empates en la posición 10, incluir a todos
        if (goleadoresOrdenados.length > 10) {
            const posicion10Goles = topGoleadores[9]?.goles || 0;
            const empatesPosicion10 = goleadoresOrdenados.filter(g => g.goles === posicion10Goles && !topGoleadores.includes(g));
            topGoleadores = [...topGoleadores, ...empatesPosicion10];
        }
    }
    
    // === CALCULAR POSICIÓN ANTERIOR Y GOLES DE LA JORNADA ACTUAL ===
    let goleadoresAnteriores = {};
    let golesJornadaActual = {};
    let numJornada = null;
    let posicionAnteriorMap = {}; // Declarar fuera del if para evitar error
    
    if (esJornadaNumerica) {
        numJornada = parseInt(jornada);
        const jornadaAnterior = numJornada - 1;
        
        if (jornadaAnterior > 0) {
            // Calcular goleadores de jornadas anteriores
            const partidosAnteriores = Object.values(partidos).filter(p => {
                const pj = parseInt(p.jornada);
                return !isNaN(pj) && pj <= jornadaAnterior && p.resultado_confirmado;
            });
            
            partidosAnteriores.forEach(p => {
                if (p.detalle_jugadores) {
                    Object.entries(p.detalle_jugadores).forEach(([tel, data]) => {
                        if (data.asistio && data.goles > 0) {
                            const nombre = jugadores[tel]?.nombre || tel;
                            if (!goleadoresAnteriores[nombre]) {
                                goleadoresAnteriores[nombre] = 0;
                            }
                            goleadoresAnteriores[nombre] += parseInt(data.goles || 0);
                        }
                    });
                }
            });
        }
        
        // Calcular posición anterior (basado en goleadoresAnteriores que tiene solo jornadas anteriores)
        const goleadoresAnterioresOrdenados = Object.entries(goleadoresAnteriores).sort((a, b) => b[1] - a[1]);
        goleadoresAnterioresOrdenados.forEach(([nombre], index) => {
            posicionAnteriorMap[nombre] = index + 1;
        });
        
        // Calcular适量的goles de esta jornada (acumulado actual - anterior)
        Object.keys(stats.goleadores).forEach(nombre => {
            const golesAnterior = goleadoresAnteriores[nombre] || 0;
            const golesActual = stats.goleadores[nombre].goles;
            golesJornadaActual[nombre] = golesActual - golesAnterior;
        });
    }
    
    // Agregar posición y cambios a cada jugador
    topGoleadores = topGoleadores.map((g, i) => {
        const posicionActual = i + 1;
        const posicionAnterior = posicionAnteriorMap[g.nombre] || null;
        let cambioPosicion = '';
        let cambioStyle = '';
        
        if (posicionAnterior !== null && posicionAnterior !== posicionActual) {
            const diferencia = posicionAnterior - posicionActual;
            if (diferencia > 0) {
                cambioPosicion = `▲${diferencia}`;
                cambioStyle = 'color:#059669; font-weight:bold;'; // verdesubio
            } else {
                cambioPosicion = `▼${Math.abs(diferencia)}`;
                cambioStyle = 'color:#dc2626; font-weight:bold;'; // rojo bajo
            }
        } else if (posicionAnterior === null && numJornada > 1) {
            cambioPosicion = '🆕';
            cambioStyle = 'color:#7c3aed; font-weight:bold;'; // morado nuevo
        }
        
        const golesEstaJornada = esJornadaNumerica ? (golesJornadaActual[g.nombre] || 0) : null;
        
        // Para jornada 1 o liguilla, no mostrar cambios
        const esPrimeraJornada = esJornadaNumerica && numJornada === 1;
        const esLiguilla = !esJornadaNumerica;
        
        return { 
            ...g, 
            posicion: posicionActual, 
            posicionAnterior: (esPrimeraJornada || esLiguilla) ? null : posicionAnterior,
            cambioPosicion: (esPrimeraJornada || esLiguilla) ? '' : (cambioPosicion || '-'),
            cambioStyle: (esPrimeraJornada || esLiguilla) ? '' : cambioStyle,
            golesEstaJornada: (esPrimeraJornada || esLiguilla) ? null : golesEstaJornada,
            esJornadaNumerica: esJornadaNumerica
        };
    });
    
    // Tabla de posiciones - solo para jornadas numéricas
    let tablaOrdenada = [];
    let recordsEquiposHTML = '';
    if (esJornadaNumerica) {
        tablaOrdenada = Object.values(stats.equipos)
            .filter(t => t.pj > 0)
            .sort((a, b) => {
                if (b.pts !== a.pts) return b.pts - a.pts;
                const difA = a.gf - a.gc;
                const difB = b.gf - b.gc;
                if (difB !== difA) return difB - difA;
                return b.gf - a.gf;
            });
        
        // === RECORD DE EQUIPOS: Más Goleador y Menos Goleado ===
        const equiposConPartidos = Object.values(stats.equipos).filter(t => t.pj > 0);
        
        if (equiposConPartidos.length > 0) {
            // Máximo GF (más goles a favor)
            const maxGF = Math.max(...equiposConPartidos.map(t => t.gf));
            const masGoleadores = equiposConPartidos.filter(t => t.gf === maxGF).map(t => t.nombre);
            
            // Mínimo GC (menos goles en contra)
            const minGC = Math.min(...equiposConPartidos.map(t => t.gc));
            const menosGoleados = equiposConPartidos.filter(t => t.gc === minGC).map(t => t.nombre);
            
            recordsEquiposHTML = `
            <div style="display:flex; gap:20px; margin-top:15px;">
                <div style="flex:1; border:2px solid #16a34a; border-radius:8px; padding:12px; background:#f0fdf4;">
                    <div style="font-weight:bold; color:#16a34a; font-size:12px; margin-bottom:5px;">🔥 MÁS GOLEADOR (GF)</div>
                    <div style="font-size:14px; font-weight:bold; color:#1e293b;">${masGoleadores.join(', ')}</div>
                    <div style="font-size:16px; font-weight:bold; color:#16a34a;">${maxGF} goles</div>
                </div>
                <div style="flex:1; border:2px solid #3b82f6; border-radius:8px; padding:12px; background:#eff6ff;">
                    <div style="font-weight:bold; color:#3b82f6; font-size:12px; margin-bottom:5px;">🛡️ MENOS GOLEADO (GC)</div>
                    <div style="font-size:14px; font-weight:bold; color:#1e293b;">${menosGoleados.join(', ')}</div>
                    <div style="font-size:16px; font-weight:bold; color:#3b82f6;">${minGC} goles</div>
                </div>
            </div>`;
        }
    }
    
    const tituloJornada = esJornadaNumerica ? 'JORNADA ' + jornada : jornada;
    
    // === LÓGICA DE PRÓXIMOS ENCUENTROS ===
    let proximosEncuentrosHTML = '';
    
    if (esJornadaNumerica) {
        // Obtener la última jornada numérica del torneo
        const jornadasNumericas = window.getJornadasConResultados();
        const ultimaJornada = jornadasNumericas.length > 0 ? Math.max(...jornadasNumericas) : 0;
        const siguienteJornada = parseInt(jornada) + 1;
        
        if (siguienteJornada > ultimaJornada) {
            // Es la última jornada - mostrar mensaje de fin de fase regular
            proximosEncuentrosHTML = `
            <div class="page-break"></div>
            <h2>📅 PRÓXIMOS ENCUENTROS</h2>
            <div style="text-align:center; padding: 20px; background: #f5f5f5; border-radius: 8px; border-left: 4px solid #fbbf24;">
                <p style="margin:0; color: #666;"><em>Fin de fase regular - Próximamente Liguilla</em></p>
            </div>`;
        } else {
            // Buscar partidos programados para la siguiente jornada (tienen fecha o están pendientes)
            const partidosProximos = Object.values(partidos).filter(p => 
                parseInt(p.jornada) === siguienteJornada && 
                (p.fecha || !p.resultado_confirmado)
            );
            
            if (partidosProximos.length > 0) {
                // Ordenar por fecha
                partidosProximos.sort((a, b) => {
                    if (!a.fecha) return 1;
                    if (!b.fecha) return -1;
                    return new Date(a.fecha) - new Date(b.fecha);
                });
                
                proximosEncuentrosHTML = `
                <div class="page-break"></div>
                <h2>📅 PRÓXIMOS ENCUENTROS - JORNADA ${siguienteJornada}</h2>
                <table>
                    <tr><th class="text-left">🏠 Local</th><th>VS</th><th class="text-right">Visitante 🛫</th><th>📅 Fecha</th><th>🕐 Hora</th></tr>
                    ${partidosProximos.map(p => `<tr>
                        <td class="text-left bold">${p.equipo_local || '-'}</td>
                        <td style="color:#94a3b8;">VS</td>
                        <td class="text-right bold">${p.equipo_visitante || '-'}</td>
                        <td>${p.fecha || 'Por definir'}</td>
                        <td>${p.hora || '-'}</td>
                    </tr>`).join('')}
                </table>`;
            }
        }
    } else {
        // Para liguilla: CUARTOS -> SEMIFINAL, SEMIFINAL -> FINAL, FINAL -> ocultar
        const fasesOrden = { 'CUARTOS': 1, 'SEMIFINAL': 2, 'FINAL': 3 };
        const faseActual = fasesOrden[jornadaStr] || 0;
        
        if (faseActual < 3) { // No es FINAL
            const siguienteFase = faseActual === 1 ? 'SEMIFINAL' : 'FINAL';
            const partidosProximos = Object.values(partidos).filter(p => 
                String(p.jornada || '').trim().toUpperCase() === siguienteFase && 
                (p.fecha || !p.resultado_confirmado)
            );
            
            if (partidosProximos.length > 0) {
                // Ordenar por fecha
                partidosProximos.sort((a, b) => {
                    if (!a.fecha) return 1;
                    if (!b.fecha) return -1;
                    return new Date(a.fecha) - new Date(b.fecha);
                });
                
                const faseColor = siguienteFase === 'SEMIFINAL' ? '#8b5cf6' : '#f59e0b';
                proximosEncuentrosHTML = `
                <div class="page-break"></div>
                <h2>📅 PRÓXIMOS ENCUENTROS - ${siguienteFase}</h2>
                <table>
                    <tr><th class="text-left">🏠 Local</th><th>VS</th><th class="text-right">Visitante 🛫</th><th>📅 Fecha</th><th>🕐 Hora</th></tr>
                    ${partidosProximos.map(p => `<tr>
                        <td class="text-left bold">${p.equipo_local || '-'}</td>
                        <td style="color:#94a3b8;">VS</td>
                        <td class="text-right bold">${p.equipo_visitante || '-'}</td>
                        <td>${p.fecha || 'Por definir'}</td>
                        <td>${p.hora || '-'}</td>
                    </tr>`).join('')}
                </table>`;
            }
        }
    }
    
    let html = `
    <!DOCTYPE html>
    <html>
    <head>
        <title>${tituloJornada} - Gol Center</title>
        <style>
            @page { size: A4; margin: 1cm; }
            body { font-family: Arial, Helvetica, sans-serif; font-size: 11px; color: #1e293b; background: #fff; }
            h1 { font-size: 22px; text-align: center; margin-bottom: 8px; color: #0f172a; font-weight: 800; }
            h2 { font-size: 14px; margin-top: 18px; margin-bottom: 10px; border-bottom: 2px solid #e2e8f0; padding-bottom: 6px; color: #334155; font-weight: 700; }
            table { width: 100%; border-collapse: collapse; margin-bottom: 14px; }
            th, td { padding: 8px 10px; text-align: center; }
            th { background: #f8fafc; color: #64748b; font-size: 9px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; }
            td { border-bottom: 1px solid #e2e8f0; }
            tr:nth-child(even) { background: #f8fafc; }
            tr:hover { background: #f1f5f9; }
            .text-left { text-align: left; }
            .text-right { text-align: right; }
            .bold { font-weight: 700; }
            .pts { color: #059669; font-weight: 700; font-size: 12px; }
            .goals { font-weight: 600; color: #334155; }
            .page-break { page-break-after: always; }
            @media print { body > * { display: none !important; } #print-jornada { display: block !important; position: static !important; left: 0 !important; } }
        </style>
    </head>
    <body>
        <div id="print-jornada">
        <h1>⚽ ${tituloJornada}</h1>
        <p style="text-align:center; margin-bottom: 20px; color: #64748b; font-size: 10px;">${fecha}</p>
        
        ${esJornadaNumerica ? `
        <h2>📊 TABLA DE POSICIONES</h2>
        <table>
            <tr><th>#</th><th class="text-left">Equipo</th><th>PJ</th><th>G</th><th>E</th><th>P</th><th>PTS</th><th>GF</th><th>GC</th><th>DG</th></tr>
            ${tablaOrdenada.map((t, i) => `<tr>
                <td class="bold">${i+1}</td>
                <td class="text-left bold">${t.nombre}</td>
                <td>${t.pj}</td>
                <td>${t.g}</td>
                <td>${t.e}</td>
                <td>${t.p}</td>
                <td class="pts">${t.pts}</td>
                <td class="goals">${t.gf}</td>
                <td class="goals">${t.gc}</td>
                <td class="goals">${t.gf - t.gc >= 0 ? '+'+(t.gf - t.gc) : t.gf - t.gc}</td>
            </tr>`).join('')}
        </table>
        
        ${recordsEquiposHTML}
        
        <div class="page-break"></div>
        ` : ''}
        
        <h2>⚽ PARTIDOS JUGADOS - ${esJornadaNumerica ? 'JORNADA ' + jornada : jornada}</h2>
        <table>
            <tr><th class="text-left">Local</th><th>Score</th><th class="text-left">Visitante</th><th>Fecha</th></tr>
            ${partidosJornada.map(p => `<tr>
                <td class="text-left bold">${p.equipo_local || '-'}</td>
                <td class="pts" style="font-size:13px;">${p.goles_local} - ${p.goles_visitante}</td>
                <td class="text-left bold">${p.equipo_visitante || '-'}</td>
                <td>${p.fecha || '-'}</td>
            </tr>`).join('')}
        </table>
        
        <div class="page-break"></div>
        
        <h2 style="font-size:16px; margin-bottom:10px;">🏆 ${esJornadaNumerica ? 'Top 10 Goleadores - Acumulado' : 'Goleadores: ' + jornadaStr}</h2>
        <table style="width:100%; border-collapse: collapse; margin-bottom: 8px; font-size:11px;">
            <tr style="background: linear-gradient(135deg, #1e3a5f 0%, #0f172a 100%); color: #fff;">
                <th style="border:1px solid #334155; padding:8px; text-align:center; width:35px;">#</th>
                <th style="border:1px solid #334155; padding:8px; text-align:left;">JUGADOR</th>
                <th style="border:1px solid #334155; padding:8px; text-align:left; width:120px;">EQUIPO</th>
                <th style="border:1px solid #334155; padding:8px; text-align:center; width:80px;">GOLES</th>
                <th style="border:1px solid #334155; padding:8px; text-align:center; width:50px;">TEND.</th>
            </tr>
            ${topGoleadores.length > 0 ? topGoleadores.map((g, i) => {
                const isTop3 = g.posicion <= 3;
                const bgColor = isTop3 ? (g.posicion === 1 ? '#fef9c3' : g.posicion === 2 ? '#f1f5f9' : '#ffedd5') : '#fff';
                const borderLeft = g.posicion === 1 ? '4px solid #eab308' : g.posicion === 2 ? '4px solid #94a3b8' : g.posicion === 3 ? '4px solid #fb923c' : '1px solid #334155';
                const medal = g.posicion === 1 ? '🥇' : g.posicion === 2 ? '🥈' : g.posicion === 3 ? '🥉' : '';
                const posicionText = g.posicion <= 3 ? '' : g.posicion;
                
                // Tendencia
                let tendencia = '';
                let tendenciaStyle = '';
                if (g.cambioPosicion && g.cambioPosicion.startsWith('▲')) {
                    tendencia = '▲';
                    tendenciaStyle = 'color:#16a34a; font-size:18px; font-weight:bold;';
                } else if (g.cambioPosicion && g.cambioPosicion.startsWith('▼')) {
                    tendencia = '▼';
                    tendenciaStyle = 'color:#dc2626; font-size:18px; font-weight:bold;';
                } else if (g.cambioPosicion === '🆕') {
                    tendencia = '🆕';
                    tendenciaStyle = 'font-size:14px;';
                } else {
                    tendencia = '●';
                    tendenciaStyle = 'color:#94a3b8; font-size:14px;';
                }
                
                // Goles con los de esta jornada
                const golesEstaJornada = g.golesEstaJornada || 0;
                const golesDisplay = g.esJornadaNumerica && g.posicionAnterior !== null 
                    ? `<span style="color:#059669; font-weight:bold;">${g.goles}</span><span style="color:#9333ea; font-size:10px;"> (+${golesEstaJornada})</span>`
                    : `<span style="color:#059669; font-weight:bold;">${g.goles}</span>`;
                
                return `<tr style="background: ${bgColor}; border-left: ${borderLeft};">
                    <td style="border:1px solid #334155; padding:8px; text-align:center; font-weight:bold; ${isTop3 ? 'color:#b45309;' : 'color:#64748b;'}">${medal || posicionText}</td>
                    <td style="border:1px solid #334155; padding:8px; text-align:left; font-weight:bold; color:#1e293b;">${g.nombre}</td>
                    <td style="border:1px solid #334155; padding:8px; text-align:left; color:#475569;">${g.equipo || '-'}</td>
                    <td style="border:1px solid #334155; padding:8px; text-align:center; font-size:14px;">${golesDisplay}</td>
                    <td style="border:1px solid #334155; padding:8px; text-align:center;">${g.esJornadaNumerica ? `<span style="${tendenciaStyle}">${tendencia}</span>` : '—'}</td>
                </tr>`;
            }).join('') : '<tr><td colspan="5" style="border:1px solid #334155; padding:12px; text-align:center;">Sin datos</td></tr>'}
        </table>
<p style="font-size:8px; color:#64748b; text-align:center;">
            ${esJornadaNumerica ? '▲ Subió ▼ Bajó ● Sin cambios 🆕 Nuevo en top (+X) Goles esta jornada' : 'Goles anotados exclusivamente en esta fase de eliminatoria'}
        </p>
        
        <!-- TABLA DE PORTEROS (GUANTE DE ORO) -->
        <h2 style="font-size:16px; margin-bottom:10px; margin-top:25px;">🧤 ${esJornadaNumerica ? 'Top Porteros - Acumulado (Guante de Oro)' : 'Guante de Oro: ' + jornadaStr}</h2>
        
        ${(() => {
            // Calcular porteros usando equipos y porteros asociados
            const porterosStats = [];
            const playersMap = {};
            for (const tel in jugadores) {
                playersMap[tel] = jugadores[tel];
            }
            
            // Calcular GC de jornadas anteriores para tendencia
            let gcAnteriores = {};
            let gcJornadaActual = {};
            
            if (esJornadaNumerica && numJornada > 1) {
                const jornadaAnterior = numJornada - 1;
                const partidosAnteriores = Object.values(partidos).filter(p => {
                    const pj = parseInt(p.jornada);
                    return !isNaN(pj) && pj <= jornadaAnterior && p.resultado_confirmado;
                });
                
                // Calcular GC anterior por equipo
                const equiposAnteriorGC = {};
                if (partidosAnteriores.length > 0) {
                    partidosAnteriores.forEach(p => {
                        const loc = (p.equipo_local || '').trim();
                        const vis = (p.equipo_visitante || '').trim();
                        if (loc && vis) {
                            equiposAnteriorGC[loc] = (equiposAnteriorGC[loc] || 0) + parseInt(p.goles_visitante || 0);
                            equiposAnteriorGC[vis] = (equiposAnteriorGC[vis] || 0) + parseInt(p.goles_local || 0);
                        }
                    });
                }
                
                // Calcular GC actual
                const equiposActualGC = {};
                if (partidosParaEstadisticas.length > 0) {
                    partidosParaEstadisticas.forEach(p => {
                        const loc = (p.equipo_local || '').trim();
                        const vis = (p.equipo_visitante || '').trim();
                        if (loc && vis) {
                            equiposActualGC[loc] = (equiposActualGC[loc] || 0) + parseInt(p.goles_visitante || 0);
                            equiposActualGC[vis] = (equiposActualGC[vis] || 0) + parseInt(p.goles_local || 0);
                        }
                    });
                }
                
                // Guardar para tendencia
                Object.keys(equiposActualGC).forEach(eq => {
                    gcAnteriores[eq] = equiposAnteriorGC[eq] || 0;
                    gcJornadaActual[eq] = equiposActualGC[eq] - (equiposAnteriorGC[eq] || 0);
                });
            }
            
            Object.values(equipos).forEach(eq => {
                const eqStats = stats.equipos[eq.nombre];
                if (eqStats && eqStats.pj > 0) {
                    let nombrePortero = eq.nombre + ' (Portero)';
                    if (eq.portero_id && playersMap[eq.portero_id]) {
                        nombrePortero = playersMap[eq.portero_id].nombre;
                    }
                    porterosStats.push({
                        nombre: nombrePortero,
                        equipo: eq.nombre,
                        gc: eqStats.gc,
                        pj: eqStats.pj,
                        gcAnterior: gcAnteriores[eq.nombre] || 0,
                        gcEstaJornada: gcJornadaActual[eq.nombre] || 0
                    });
                }
            });
            
            // Ordenar por menos GC
            porterosStats.sort((a, b) => {
                if (a.gc !== b.gc) return a.gc - b.gc;
                return b.pj - a.pj;
            });
            
            // Calcular posición anterior
            let posicionAnteriorMap = {};
            if (esJornadaNumerica && numJornada > 1) {
                const porterosAnteriores = Object.values(equipos).map(eq => {
                    return { nombre: eq.nombre, gc: gcAnteriores[eq.nombre] || 0 };
                }).filter(p => p.gc > 0).sort((a, b) => a.gc - b.gc);
                
                porterosAnteriores.forEach((p, idx) => {
                    posicionAnteriorMap[p.nombre] = idx + 1;
                });
            }
            
            const topPorteros = porterosStats.slice(0, 10);
            
            return `
            <table style="width:100%; border-collapse: collapse; margin-bottom:8px; font-size:11px;">
                <tr style="background: linear-gradient(135deg, #065f46 0%, #0f172a 100%); color: #fff;">
                    <th style="border:1px solid #334155; padding:8px; text-align:center; width:35px;">#</th>
                    <th style="border:1px solid #334155; padding:8px; text-align:left;">PORTERO</th>
                    <th style="border:1px solid #334155; padding:8px; text-align:left; width:120px;">EQUIPO</th>
                    <th style="border:1px solid #334155; padding:8px; text-align:center; width:60px;">PJ</th>
                    <th style="border:1px solid #334155; padding:8px; text-align:center; width:60px;">GC</th>
                    <th style="border:1px solid #334155; padding:8px; text-align:center; width:40px;">TEND.</th>
                </tr>
                ${topPorteros.length > 0 ? topPorteros.map((p, i) => {
                    const pos = i + 1;
                    const posAnterior = posicionAnteriorMap[p.equipo];
                    let tendencia = '';
                    let tendenciaStyle = '';
                    
                    if (esJornadaNumerica && numJornada > 1 && posAnterior) {
                        const diff = pos - posAnterior;
                        if (diff < 0) {
                            tendencia = '▲';
                            tendenciaStyle = 'color:#16a34a; font-size:18px; font-weight:bold;';
                        } else if (diff > 0) {
                            tendencia = '▼';
                            tendenciaStyle = 'color:#dc2626; font-size:18px; font-weight:bold;';
                        } else {
                            tendencia = '●';
                            tendenciaStyle = 'color:#94a3b8; font-size:14px;';
                        }
                    } else if (esJornadaNumerica && numJornada === 1) {
                        tendencia = '🆕';
                        tendenciaStyle = 'font-size:14px;';
                    } else {
                        tendencia = '—';
                        tendenciaStyle = 'color:#94a3b8;';
                    }
                    
                    const gcThisJornada = p.gcEstaJornada > 0 ? ` <span style="color:#9333ea; font-size:9px;">(+${p.gcEstaJornada})</span>` : '';
                    
                    const isTop3 = pos <= 3;
                    const bgColor = isTop3 ? (pos === 1 ? '#ecfdf5' : pos === 2 ? '#f1f5f9' : '#ffedd5') : '#fff';
                    const borderLeft = pos === 1 ? '4px solid #10b981' : pos === 2 ? '4px solid #94a3b8' : pos === 3 ? '4px solid #fb923c' : '1px solid #334155';
                    const medal = pos === 1 ? '🥇' : pos === 2 ? '🥈' : pos === 3 ? '🥉' : '';
                    
                    return `<tr style="background: ${bgColor}; border-left: ${borderLeft};">
                        <td style="border:1px solid #334155; padding:8px; text-align:center; font-weight:bold; ${isTop3 ? 'color:#047857;' : 'color:#64748b;'}">${medal || pos}</td>
                        <td style="border:1px solid #334155; padding:8px; text-align:left; font-weight:bold; color:#1e293b;">${p.nombre}</td>
                        <td style="border:1px solid #334155; padding:8px; text-align:left; color:#475569;">${p.equipo}</td>
                        <td style="border:1px solid #334155; padding:8px; text-align:center;">${p.pj}</td>
                        <td style="border:1px solid #334155; padding:8px; text-align:center; font-weight:bold; ${p.gc <= 5 ? 'color:#10b981;' : p.gc <= 10 ? 'color:#eab308;' : 'color:#dc2626;'}">${p.gc}${gcThisJornada}</td>
                        <td style="border:1px solid #334155; padding:8px; text-align:center;">${esJornadaNumerica ? `<span style="${tendenciaStyle}">${tendencia}</span>` : '—'}</td>
                    </tr>`;
                }).join('') : '<tr><td colspan="6" style="border:1px solid #334155; padding:12px; text-align:center;">Sin datos</td></tr>'}
            </table>
            <p style="font-size:8px; color:#64748b; text-align:center; margin-bottom:20px;">
                🧤 ▲ Subió ▼ Bajó ● Sin cambios 🆕 Nuevo en top (+X) GC esta jornada - Menor GC = Mejor
            </p>`;
        })()}
        
        ${proximosEncuentrosHTML}
        
        <p style="margin-top:20px; font-size:8px; text-align:center">Generado por Gol Center - ${new Date().toISOString()}</p>
        </div>
    </body>
    </html>`;
    
    const printDiv = document.createElement('div');
    printDiv.id = 'print-jornada';
    printDiv.innerHTML = html;
    printDiv.style.cssText = 'position:absolute; left:-9999px; top:0; width:100%;';
    document.body.appendChild(printDiv);
    
    window.print();
    setTimeout(() => printDiv.remove(), 1000);
};

// Obtener jornadas disponibles para mostrar botón de descarga (SOLO NÚMEROS)
window.getJornadasConResultados = function() {
    if (!datosGlobales || !datosGlobales.partidos) return [];
    
    const jornadas = new Set();
    Object.values(datosGlobales.partidos).forEach(p => {
        if (p.jornada && p.resultado_confirmado) {
            const j = String(p.jornada).trim().toUpperCase();
            // Solo incluir si es un número (1, 2, 3...) - exclude CUARTOS, SEMIFINAL, FINAL, LIGUILLA
            if (j !== 'CUARTOS' && j !== 'SEMIFINAL' && j !== 'FINAL' && j !== 'LIGUILLA' && !isNaN(parseInt(j))) {
                jornadas.add(parseInt(j));
            }
        }
    });
    
    return Array.from(jornadas).sort((a, b) => a - b);
};

// Obtener fases de liguilla (CUARTOS, SEMIFINAL, FINAL)
window.getFasesLiguilla = function() {
    if (!datosGlobales || !datosGlobales.partidos) return [];
    
    const fasesSet = new Set();
    Object.values(datosGlobales.partidos).forEach(p => {
        if (p.jornada && p.resultado_confirmado) {
            const jornada = String(p.jornada).trim().toUpperCase();
            if (jornada === 'CUARTOS' || jornada === 'SEMIFINAL' || jornada === 'FINAL') {
                fasesSet.add(jornada);
            }
        }
    });
    
    // Orden específico: CUARTOS -> SEMIFINAL -> FINAL
    const orden = { 'CUARTOS': 1, 'SEMIFINAL': 2, 'FINAL': 3 };
    return Array.from(fasesSet).sort((a, b) => (orden[a] || 99) - (orden[b] || 99));
};

// Renderizar botones de descarga por jornada
function renderizarBotonesDescargaJornadas() {
    const contenedor = document.getElementById('contenedor-botones-jornadas');
    if (!contenedor) return;
    
    const jornadas = window.getJornadasConResultados();
    const fases = window.getFasesLiguilla();
    
    
    if (jornadas.length === 0 && fases.length === 0) {
        contenedor.innerHTML = '<span class="text-slate-500 text-sm">No hay jornadas finalizadas aún</span>';
        return;
    }
    
    let html = '';
    
    // Botones de jornadas numéricas
    if (jornadas.length > 0) {
        html += '<div class="w-full mb-2"><span class="text-xs font-bold text-slate-400">JORNADAS</span></div>';
        jornadas.forEach(function(j) {
            html += '<button data-jornada="' + j + '" class="btn-pdf-jornada bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-bold px-4 py-2 rounded-lg flex items-center gap-2 transition">📄 Jornada ' + j + '</button>';
        });
    }
    
    // Botones de fases de liguilla
    if (fases.length > 0) {
        html += '<div class="w-full mt-4 mb-2"><span class="text-xs font-bold text-slate-400">LIGUILLA / PLAY-OFFS</span></div>';
        fases.forEach(function(f) {
            html += '<button data-jornada="' + f + '" class="btn-pdf-liguilla bg-blue-600 hover:bg-blue-500 text-white text-xs font-bold px-4 py-2 rounded-lg flex items-center gap-2 transition">🏆 ' + f + '</button>';
        });
    }
    
    contenedor.innerHTML = html;
    
    // Agregar event listeners después de crear el HTML
    contenedor.querySelectorAll('.btn-pdf-jornada, .btn-pdf-liguilla').forEach(function(btn) {
        btn.addEventListener('click', function() {
            const jornada = this.getAttribute('data-jornada');
            window.descargarPDFJornada(jornada);
        });
    });
}