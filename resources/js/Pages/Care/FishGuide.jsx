import React from 'react';
import { Head, Link } from '@inertiajs/react';

const waterLabels = { dulce: 'Agua dulce', salada: 'Agua salada' };
const careLabels = { inicial: 'Inicial', intermedio: 'Intermedio', avanzado: 'Avanzado' };

function VolumeCard({ label, values, tone }) {
    if (!values) return null;
    return <article className={`rounded-2xl border p-5 ${tone}`}>
        <p className="text-xs font-extrabold uppercase tracking-[0.14em]">{label}</p>
        <p className="mt-3 text-3xl font-black">{values.minimum}–{values.maximum} L</p>
        <p className="mt-1 text-sm">Rango de mínimos registrados para {values.count} especies activas.</p>
    </article>;
}

export default function FishGuide({ species = [], volumes = {} }) {
    const fresh = species.filter((fish) => fish.water_type === 'dulce');
    const marine = species.filter((fish) => fish.water_type === 'salada');

    return <div className="min-h-screen bg-slate-50 text-[#0b192c]">
        <Head title="Guía básica de cuidados · AQUARIUM" />
        <header className="sticky top-0 z-20 border-b border-white/10 bg-[#0b192c] text-white shadow-lg">
            <div className="mx-auto flex max-w-7xl items-center justify-between gap-4 px-5 py-4 sm:px-8">
                <Link href="/" className="font-black tracking-wide">AQUARIUM <span className="ml-1 text-[10px] font-bold text-[#08c2cd]">VIDA ACUÁTICA</span></Link>
                <nav className="flex items-center gap-4 text-sm" aria-label="Navegación principal">
                    <Link href="/" className="text-slate-300 hover:text-white">Inicio</Link>
                    <Link href="/catalogo" className="font-bold text-[#08c2cd]">Catálogo</Link>
                    <Link href="/#clinica" className="text-slate-300 hover:text-white">Clínica</Link>
                    <Link href="/#informacion" className="text-slate-300 hover:text-white">Información</Link>
                </nav>
            </div>
        </header>

        <main className="mx-auto max-w-7xl px-5 py-10 sm:px-8 sm:py-14">
            <section className="overflow-hidden rounded-3xl bg-[#0b192c] px-6 py-9 text-white sm:px-10 sm:py-12">
                <p className="text-xs font-extrabold uppercase tracking-[0.17em] text-[#08c2cd]">Información general · sin solicitudes ni reservaciones</p>
                <h1 className="mt-3 max-w-3xl text-3xl font-black tracking-tight sm:text-4xl">Cuidados básicos para peces del catálogo</h1>
                <p className="mt-4 max-w-3xl text-sm leading-6 text-slate-300 sm:text-base">Una guía de referencia para planear el acuario y mantener condiciones estables. Las necesidades varían por especie, tamaño adulto, número de ejemplares y compatibilidad; la ficha del pez tiene prioridad sobre cualquier cifra general.</p>
            </section>

            <section className="mt-9" aria-labelledby="volume-title">
                <div className="max-w-3xl">
                    <p className="text-xs font-bold uppercase tracking-[0.14em] text-teal-700">Espacio recomendado</p>
                    <h2 id="volume-title" className="mt-2 text-2xl font-extrabold">No existe un volumen único para todos los peces</h2>
                    <p className="mt-2 text-sm leading-6 text-slate-600">Como referencia para iniciar un pequeño acuario comunitario de agua dulce, considera al menos <b>60 litros</b>. Es solo una base de planeación: no sustituye el mínimo por especie ni permite mezclar peces incompatibles. En el catálogo hay especies que requieren acuarios mucho mayores.</p>
                </div>
                <div className="mt-5 grid gap-4 sm:grid-cols-2">
                    <VolumeCard label="Especies de agua dulce" values={volumes.dulce} tone="border-cyan-200 bg-cyan-50 text-cyan-950" />
                    <VolumeCard label="Especies de agua salada" values={volumes.salada} tone="border-violet-200 bg-violet-50 text-violet-950" />
                </div>
                <p className="mt-3 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm leading-6 text-amber-950">El mínimo de la ficha corresponde a esa especie y tamaño de grupo. Por ejemplo, un pez que requiera 200 o 400 L no debe mantenerse en el acuario de referencia de 60 L.</p>
            </section>

            <section className="mt-10 grid gap-4 md:grid-cols-2" aria-label="Elementos básicos del acuario">
                <article className="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-900/10">
                    <h2 className="text-lg font-extrabold">Equipo básico</h2>
                    <ul className="mt-4 space-y-3 text-sm leading-6 text-slate-700">
                        <li><b>Filtro biológico</b> dimensionado al volumen y a la población, con circulación adecuada.</li>
                        <li><b>Termómetro</b> y, para especies tropicales que lo requieran, calentador regulable.</li>
                        <li><b>Acondicionador de agua</b> para neutralizar cloro y cloraminas del agua de red.</li>
                        <li><b>Kit para medir la calidad del agua</b>: temperatura, pH, amoniaco, nitrito y nitrato; en marinos también salinidad.</li>
                        <li><b>Tapa e iluminación</b> según el tipo de montaje y las especies.</li>
                    </ul>
                </article>
                <article className="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-900/10">
                    <h2 className="text-lg font-extrabold">Agua y puesta en marcha</h2>
                    <ul className="mt-4 space-y-3 text-sm leading-6 text-slate-700">
                        <li>Agua dulce y salada son sistemas distintos; no mezcles especies ni productos de ambos tipos.</li>
                        <li>Usa agua acondicionada y estabiliza temperatura y parámetros de acuerdo con la ficha de cada especie.</li>
                        <li>Antes de introducir peces, deja madurar el filtro y establece el ciclo biológico; el amoniaco y el nitrito deben mantenerse en niveles seguros.</li>
                        <li>Controla cambios graduales y realiza mantenimiento parcial; evita vaciar y reiniciar todo el acuario de manera rutinaria.</li>
                    </ul>
                </article>
                <article className="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-900/10">
                    <h2 className="text-lg font-extrabold">Alimento</h2>
                    <p className="mt-3 text-sm leading-6 text-slate-700">Elige alimento completo compatible con la dieta de la especie: hojuelas o pellets para peces que los acepten, y dietas especializadas cuando la ficha lo indique. Ofrece porciones pequeñas que puedan consumir sin dejar sobrantes; el exceso deteriora el agua.</p>
                </article>
                <article className="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-900/10">
                    <h2 className="text-lg font-extrabold">Arena y sustratos</h2>
                    <p className="mt-3 text-sm leading-6 text-slate-700">Selecciona arena o grava segura según el comportamiento de la especie y el diseño del acuario. Para montajes marinos, los sustratos minerales pueden modificar pH y alcalinidad; verifica su efecto antes de usarlos. El sustrato no reemplaza la filtración ni el mantenimiento.</p>
                </article>
            </section>

            <section className="mt-12" aria-labelledby="catalog-species-title">
                <div className="mb-5">
                    <p className="text-xs font-bold uppercase tracking-[0.14em] text-teal-700">Datos del catálogo actual</p>
                    <h2 id="catalog-species-title" className="mt-2 text-2xl font-extrabold">Mínimos registrados por especie</h2>
                    <p className="mt-2 text-sm text-slate-600">Los litros son el mínimo indicado en cada ficha; revisa también el grupo mínimo, temperamento y cuidados particulares.</p>
                </div>
                <div className="grid gap-5 lg:grid-cols-2">
                    {[["Agua dulce", fresh], ["Agua salada", marine]].map(([label, items]) => <section key={label} className="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-900/10">
                        <h3 className="border-b border-slate-100 px-5 py-4 font-extrabold">{label}</h3>
                        <div className="divide-y divide-slate-100">
                            {items.map((fish) => <article key={fish.id} className="grid grid-cols-[minmax(0,1fr)_auto] gap-3 px-5 py-3 text-sm">
                                <div><p className="font-bold">{fish.name}</p>{fish.scientific_name && <p className="text-xs italic text-slate-500">{fish.scientific_name}</p>}<p className="mt-1 text-xs text-slate-600">Grupo mínimo: {fish.minimum_group_size || 'según compatibilidad'} · Cuidados: {careLabels[fish.care_level] || 'según ficha'}</p></div>
                                <p className="self-center rounded-lg bg-slate-50 px-3 py-2 font-extrabold text-teal-800">{fish.minimum_tank_liters ? `${fish.minimum_tank_liters} L` : 'Consultar'}</p>
                            </article>)}
                        </div>
                    </section>)}
                </div>
            </section>

            <aside className="mt-10 rounded-2xl border border-slate-200 bg-white p-5 text-xs leading-5 text-slate-600">
                <p className="font-extrabold text-[#0b192c]">Fuentes de consulta</p>
                <p className="mt-2">IMIPAS, <a className="font-semibold text-teal-800 underline" href="https://www.gob.mx/imipas/acciones-y-programas/acuacultura-acuario-para-peces-de-ornato" target="_blank" rel="noreferrer">Acuario para peces de ornato</a>; University of Florida IFAS, <a className="font-semibold text-teal-800 underline" href="https://edis.ifas.ufl.edu/publication/FA124" target="_blank" rel="noreferrer">responsible aquarium fish ownership</a> y <a className="font-semibold text-teal-800 underline" href="https://ask.ifas.ufl.edu/publication/fa031" target="_blank" rel="noreferrer">ciclo del nitrógeno</a>; CDC, <a className="font-semibold text-teal-800 underline" href="https://www.cdc.gov/healthy-pets/about/fish.html" target="_blank" rel="noreferrer">fish care basics</a>.</p>
                <p className="mt-2">La información es educativa y general. La salud, compatibilidad y necesidades de cada ejemplar requieren revisar su ficha y, si hay signos de enfermedad, consultar a un profesional veterinario.</p>
            </aside>
        </main>

        <footer className="bg-[#0b192c] px-5 py-6 text-center text-xs text-slate-400">AQUARIUM · Guía informativa, sin solicitudes ni reservaciones</footer>
    </div>;
}
