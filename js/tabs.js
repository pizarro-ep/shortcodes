// Using tailwindcss class for style

document.addEventListener("DOMContentLoaded", () => {
	const tabs = document.querySelectorAll("[data-tab]");
	const contents = document.querySelectorAll("[data-content]");

	// Obtener la clave basada en la ruta sin el ID
	const basePath = window.location.pathname.replace(/\/\d+$/, ""); // Remueve el ID al final
	const pageKey = `activeTab_${basePath}`;

	// Recuperar el tab guardado en localStorage o usar el primero
	const savedTab = localStorage.getItem(pageKey) || tabs[0].getAttribute("data-tab");

	tabs.forEach((tab) => {
		tab.addEventListener("click", function () {
			const target = this.getAttribute("data-tab");

			// Guardar en localStorage con la clave basada en la ruta sin ID
			localStorage.setItem(pageKey, target);

			// Quitar clases activas de todos los tabs
			tabs.forEach((t) => t.classList.remove("bg-gray-50", "text-black"));
			tabs.forEach((t) =>
				t.classList.add("hover:bg-gray-50", "hover:text-gray-900")
			);

			// Agregar clase activa al tab seleccionado
			this.classList.remove("hover:bg-gray-50", "hover:text-gray-900");
			this.classList.add("bg-gray-50", "text-black");

			// Ocultar todo el contenido
			contents.forEach((content) => content.classList.add("hidden"));

			// Mostrar el contenido correspondiente
			document
				.querySelector(`[data-content="${target}"]`)
				.classList.remove("hidden");
		});
	});

	// Restaurar el tab guardado
	const activeTab = document.querySelector(`[data-tab="${savedTab}"]`);
	if (activeTab) {
		activeTab.click();
	} 
});


// TAB HTML TEMPLATE
/*
<!-- Tabs -->
<ul class="flex-column min-w-24 max-w-32 text-sm font-medium text-gray-500 bg-gray-200">
    <li>
        <button data-tab="tab-content-1" class="inline-flex items-center px-4 py-3 text-white bg-gray-50 w-full">
            Tab 1
        </button>
    </li>
    <li>
        <button data-tab="tab-content-2" class="inline-flex items-center px-4 py-3 hover:text-gray-900 hover:bg-gray-50 w-full">
            Tab 2
        </button>
    </li>
</ul>


<!-- Contenido -->
<div class="w-full bg-gray-50 p-6 rounded-lg">
	<div data-content="tab-loading" class="h-[250px]">Cargando....</div>
    <div data-content="tab-content-1" class="text-medium text-gray-500"> 
        Content 1
    </div>
    <div data-content="tab-content-2" class="text-medium text-gray-500 hidden">
        Content 2
    </div>
</div>
*/