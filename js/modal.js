// Using tailwindcss class for style
function _initModal() {
	// ABRIR MODAL
	document.querySelectorAll("[data-modal-toggle]").forEach((btn) => {
		btn.addEventListener("click", function () {
			const modalId = btn.getAttribute("data-modal-toggle");
			const modal = document.getElementById(modalId);
			if (!modal) return;

			const modalContent = modal.querySelector(".modal-content");

			modal.classList.remove("hidden");
			setTimeout(() => {
				modalContent.classList.remove("opacity-0", "scale-90");
				modalContent.classList.add("opacity-100", "scale-100");
			}, 10);
		});
	});

	// CERRAR MODAL (Cualquier botón con `data-modal-close`)
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
	}, 300);
}


// MODAL HTML TEMPLATE
/*
<button data-modal-toggle="modal-questions" class="inline-flex items-center text-slate-500 bg-slate-500/20 hover:bg-slate-500/30 focus:ring-2 focus:outline-none focus:ring-slate-300 rounded-lg text-sm w-full sm:w-auto px-5 py-2.5 text-center font-bold">Añadir</button>
<!-- MODAL -->
<div id="modal-questions" data-modal class="hidden fixed inset-0 z-10 bg-black/50 backdrop-blur-sm">
    <div class="flex flex-col items-center justify-center min-h-screen">
        <div class="modal-content bg-white p-6 max-w-sm rounded-lg shadow-lg transform opacity-0 scale-90 transition-all duration-300">
            <h3 class="text-lg font-bold text-gray-900 mb-2">Modal 1</h3>
            <p>Este es el contenido del modal.</p>
            <button data-modal-close="modal-questions" class="mt-4 bg-gray-500 text-white px-4 py-2 rounded">Cerrar</button>
        </div>
    </div>
</div>
*/