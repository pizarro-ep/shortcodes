// Using tailwindcss class for style
function _initModal() {
	// ABRIR MODAL
	document.querySelectorAll("[data-modal-toggle]").forEach((btn) => {
		btn.addEventListener("click", function () {
			const modalId = btn.getAttribute("data-modal-toggle");
			const modal = document.getElementById(modalId);
			if (!modal) return;

			const modalContent = modal.querySelector(".modal-content");

			modal.classList.add("modal-open"); // Marcar como abierto
			modal.classList.remove("hidden");

			setTimeout(() => {
				modalContent.classList.remove("opacity-0", "scale-90");
				modalContent.classList.add("opacity-100", "scale-100");

				// Disparar un evento personalizado cuando se abre el modal
				document.dispatchEvent(
					new CustomEvent("modalOpened", { detail: { modalId } })
				);
			}, 10);
		});
	});

	// CERRAR MODAL
	document.querySelectorAll("[data-modal-close]").forEach((btn) => {
		btn.addEventListener("click", function () {
			const modalId = btn.getAttribute("data-modal-close");
			_closeModal(modalId);
		});
	});

	// CERRAR MODAL HACIENDO CLIC FUERA DEL CONTENIDO
	document.querySelectorAll("[data-modal]").forEach((modal) => {
		modal.addEventListener("click", function (e) {
			if (e.target === modal) {
				_closeModal(modal.id);
			}
		});
	});
}

// Función para cerrar modal con animaciones
function _closeModal(modalId) {
	const modal = document.getElementById(modalId);
	if (!modal) return;

	const modalContent = modal.querySelector(".modal-content");

	modalContent.classList.remove("opacity-100", "scale-100");
	modalContent.classList.add("opacity-0", "scale-90");

	setTimeout(() => {
		modal.classList.add("hidden");
		modal.classList.remove("modal-open"); // Quitar la clase cuando se cierre

		// Disparar un evento personalizado cuando se cierra el modal
		document.dispatchEvent(
			new CustomEvent("modalClosed", { detail: { modalId } })
		);
	}, 300);
}

// EVENTOS
document.addEventListener("modalOpened", (event) => {
	if (event.detail.modalId === "ID") {
		// EVENTO AL ABRIR MODAL
	}
});
document.addEventListener("modalClosed", (event) => {
	if (event.detail.modalId === "ID") {
		// EVENTO AL CERRAR MODAL
	}
});


// MODAL HTML TEMPLATE
/*
<!-- BOTÓN PARA ABRIR MODAL -->
<button data-modal-toggle="modal-questions" class="inline-flex items-center text-slate-500 bg-slate-500/20 hover:bg-slate-500/30 focus:ring-2 focus:outline-none focus:ring-slate-300 rounded-lg text-sm w-full sm:w-auto px-5 py-2.5 text-center font-bold">Añadir</button>
<!-- MODAL -->
<div id="modal-questions" data-modal class="hidden fixed inset-0 z-10 bg-black/50 backdrop-blur-sm">
	<div class="flex flex-col items-center justify-center min-h-screen">
		<div class="modal-content bg-white p-6 min-w-sm max-w-md rounded-lg shadow-lg transform opacity-0 scale-90 transition-all duration-300">
			<div class="text-slate-500 pb-3">Casos</div>
			<div class="w-full flex flex-wrap gap-2 p-3 bg-gray-500/20 rounded" id="questions-data"></div>
			<button type="button" class="mt-4 bg-gray-500 text-white px-4 py-2 rounded" data-modal-close="modal-questions">Cerrar</button>
		</div>
	</div>
</div>
*/