const burger = document.querySelector(".header__burger");
const nav = document.querySelector(".header__navigation-list");
const navLinks = document.querySelectorAll(".header__navigation-link");

if (burger && nav) {
	burger.addEventListener("click", () => {
		const isActive = burger.classList.toggle("header__burger--active");
		nav.classList.toggle("active");
		document.body.classList.toggle("no-scroll");
		burger.setAttribute("aria-expanded", String(isActive));
		burger.setAttribute("aria-label", isActive ? "Закрыть меню" : "Открыть меню");
	});

	navLinks.forEach((link) => {
		link.addEventListener("click", () => {
			burger.classList.remove("header__burger--active");
			nav.classList.remove("active");
			document.body.classList.remove("no-scroll");
			burger.setAttribute("aria-expanded", "false");
			burger.setAttribute("aria-label", "Открыть меню");
		});
	});
}
