import React from 'react';

export default function Home() {
    return (
        <main className="mx-auto flex min-h-screen max-w-5xl items-center px-6 py-16">
            <section>
                <p className="text-sm font-semibold uppercase tracking-[0.2em] text-cyan-400">
                    Plataforma local de acuariofilia
                </p>
                <h1 className="mt-4 text-4xl font-bold tracking-tight sm:text-6xl">
                    AQUARIUM
                </h1>
                <p className="mt-5 max-w-2xl text-lg leading-8 text-slate-300">
                    Entorno de desarrollo preparado. La primera entrega funcional
                    se definirá desde la matriz de requisitos de Main Work.
                </p>
            </section>
        </main>
    );
}
