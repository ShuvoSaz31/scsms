setTimeout(()=>document.querySelectorAll('.alert').forEach(x=>x.style.opacity='.85'),3000);

(() => {
	const categorySelect = document.querySelector("[data-category-select]");
	const subcategorySelect = document.querySelector("[data-subcategory-select]");
	if (!categorySelect || !subcategorySelect) return;

	const placeholder = subcategorySelect.options[0];
	const filterSubcategories = () => {
		const categoryId = categorySelect.value;
		const matchingOptions = Array.from(subcategorySelect.options).slice(1).filter(option => option.dataset.categoryId === categoryId);
		for (const option of Array.from(subcategorySelect.options).slice(1)) {
			option.hidden = !categoryId || option.dataset.categoryId !== categoryId;
		}
		if (subcategorySelect.selectedOptions[0]?.hidden) subcategorySelect.value = "";
		placeholder.textContent = !categoryId ? "Choose a category first" : matchingOptions.length ? "Select a subcategory" : "No subcategories available";
		subcategorySelect.disabled = !categoryId || matchingOptions.length === 0;
	};

	categorySelect.addEventListener("change", filterSubcategories);
	filterSubcategories();
})();

(() => {
	const sidebarToggle = document.querySelector("#sidebar-toggle");
	if (!sidebarToggle) return;

	const desktopLayout = window.matchMedia("(min-width: 801px)");
	const savedExpandedState = localStorage.getItem("scsms-sidebar-expanded") === "true";
	if (desktopLayout.matches) sidebarToggle.checked = savedExpandedState;

	sidebarToggle.addEventListener("change", () => {
		if (desktopLayout.matches) {
			localStorage.setItem("scsms-sidebar-expanded", String(sidebarToggle.checked));
		}
	});
})();

(() => {
	const photoInput = document.querySelector("[data-profile-photo]");
	const preview = document.querySelector("[data-profile-preview]");
	if (!photoInput || !preview) return;

	let previewUrl = "";
	photoInput.addEventListener("change", () => {
		const file = photoInput.files && photoInput.files[0];
		if (!file || !["image/jpeg", "image/png", "image/webp"].includes(file.type)) return;
		if (previewUrl) URL.revokeObjectURL(previewUrl);
		previewUrl = URL.createObjectURL(file);
		let image = preview.querySelector("img");
		if (!image) {
			image = document.createElement("img");
			preview.append(image);
		}
		image.alt = "Selected profile photo preview";
		image.src = previewUrl;
		const fallback = preview.querySelector("span");
		if (fallback) fallback.remove();
	});
})();
