document.addEventListener("DOMContentLoaded", function () {
	const form = document.getElementById("consultation-form");
	if (!form) return;

	const nameInput = document.getElementById("name");
	const phoneInput = document.getElementById("phone");
	const submitBtn = form.querySelector('button[type="submit"]');
	const statusDiv = form.querySelector(".form__status");

	// Phone Mask
	function setCursorPosition(pos, elem) {
		elem.focus();
		if (elem.setSelectionRange) elem.setSelectionRange(pos, pos);
		else if (elem.createTextRange) {
			var range = elem.createTextRange();
			range.collapse(true);
			range.moveEnd("character", pos);
			range.moveStart("character", pos);
			range.select();
		}
	}

	function mask(event) {
		var matrix = "+7 (___) ___-__-__",
			i = 0,
			def = matrix.replace(/\D/g, ""),
			val = this.value.replace(/\D/g, "");

		if (def.length >= val.length) val = def;

		this.value = matrix.replace(/./g, function (a) {
			return /[_\d]/.test(a) && i < val.length ? val.charAt(i++) : i >= val.length ? "" : a;
		});

		if (event.type == "blur") {
			if (this.value.length == 2) this.value = "";
		} else {
			// setCursorPosition(this.value.length, this)
		}
	}

	phoneInput.addEventListener("input", mask, false);
	phoneInput.addEventListener("focus", mask, false);
	phoneInput.addEventListener("blur", mask, false);

	// Form Submission
	form.addEventListener("submit", function (e) {
		e.preventDefault();

		// Reset errors
		resetErrors();
		statusDiv.textContent = "";
		statusDiv.className = "form__status";
		statusDiv.classList.remove("success", "error");

		let isValid = true;

		// Name validation
		const nameValue = nameInput.value.trim();
		if (nameValue.length < 2 || nameValue.length > 20) {
			showError(nameInput, "Имя должно быть от 2 до 20 символов");
			isValid = false;
		}

		// Phone validation
		const phoneValue = phoneInput.value.replace(/\D/g, "");
		// +7 is 1 digit (7), code is 3 (XXX), number is 7 (XXX-XX-XX) -> total 11 digits
		if (phoneValue.length !== 11) {
			showError(phoneInput, "Введите корректный номер телефона");
			isValid = false;
		}

		if (!isValid) return;

		// Submit state
		const originalBtnText = submitBtn.textContent;
		submitBtn.disabled = true;
		submitBtn.textContent = "Отправляется...";

		const formData = new FormData(form);

		fetch("files/sendmail.php", {
			method: "POST",
			body: formData,
		})
			.then((response) => {
				if (response.ok) {
					return response.text();
				}
				throw new Error("Network response was not ok.");
			})
			.then((data) => {
				statusDiv.textContent = "Мы скоро свяжемся с вами";
				statusDiv.classList.add("success");
				form.reset();
			})
			.catch((error) => {
				console.error("Error:", error);
				statusDiv.textContent = "Произошла ошибка при отправке.";
				statusDiv.classList.add("error");
			})
			.finally(() => {
				submitBtn.disabled = false;
				submitBtn.textContent = originalBtnText;
			});
	});

	function showError(input, message) {
		input.classList.add("error");
		const group = input.closest(".form__group");
		let errorSpan = group.querySelector(".form__error");
		if (!errorSpan) {
			errorSpan = document.createElement("span");
			errorSpan.className = "form__error";
			group.appendChild(errorSpan);
		}
		errorSpan.textContent = message;
	}

	function resetErrors() {
		const inputs = form.querySelectorAll(".form__input");
		inputs.forEach((input) => {
			input.classList.remove("error");
			const group = input.closest(".form__group");
			const errorSpan = group.querySelector(".form__error");
			if (errorSpan) errorSpan.textContent = "";
		});
	}
});
