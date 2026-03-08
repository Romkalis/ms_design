document.addEventListener("DOMContentLoaded", function () {
	const scrollTopBtn = document.getElementById("scrollTop");

	if (!scrollTopBtn) return;

	// Show/hide button on scroll
	window.addEventListener("scroll", function () {
		if (window.scrollY > 300) {
			scrollTopBtn.classList.add("is-visible");
		} else {
			scrollTopBtn.classList.remove("is-visible");
		}
	});

	// Smooth scroll to top
	scrollTopBtn.addEventListener("click", function () {
		window.scrollTo({
			top: 0,
			behavior: "smooth"
		});
	});
});
