document.addEventListener("DOMContentLoaded", function () {
	const forms = document.querySelectorAll('form#consultation-form, form[action="/files/mail.php"], form[action$="/files/mail.php"]');
	if (!forms.length) return;

	forms.forEach((form) => {
		const nameInput = form.querySelector('input[name="name"]');
		const phoneInput = form.querySelector('input[name="phone"]');
		const submitBtn = form.querySelector('button[type="submit"]');
		const statusDiv = form.querySelector(".form__status");
		const tsInput = form.querySelector('input[name="form_ts"]');

		if (!nameInput || !phoneInput || !submitBtn || !statusDiv) return;

		if (tsInput) tsInput.value = String(Date.now());

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
			}
		}

		phoneInput.addEventListener("input", mask, false);
		phoneInput.addEventListener("focus", mask, false);
		phoneInput.addEventListener("blur", mask, false);

		form.addEventListener("submit", function (e) {
			e.preventDefault();

			resetErrors();
			statusDiv.textContent = "";
			statusDiv.className = "form__status";
			statusDiv.classList.remove("success", "error");

			let isValid = true;

			const nameValue = nameInput.value.trim();
			if (nameValue.length < 2 || nameValue.length > 20) {
				showError(nameInput, "Имя должно быть от 2 до 20 символов");
				isValid = false;
			}

			const phoneValue = phoneInput.value.replace(/\D/g, "");
			if (phoneValue.length !== 11) {
				showError(phoneInput, "Введите корректный номер телефона");
				isValid = false;
			}

			if (!isValid) return;

			if (tsInput && !tsInput.value) tsInput.value = String(Date.now());

			const originalBtnText = submitBtn.textContent;
			submitBtn.disabled = true;
			submitBtn.textContent = "Отправляется...";

			const formData = new FormData(form);
			const endpoint = form.getAttribute("action") || "/files/mail.php";

			fetch(endpoint, {
				method: "POST",
				body: formData,
			})
				.then((response) => response.text().then((text) => ({ok: response.ok, status: response.status, text})))
				.then(({ok, status, text}) => {
					if (ok) {
						statusDiv.textContent = "Мы скоро свяжемся с вами";
						statusDiv.classList.add("success");
						form.reset();
						if (tsInput) tsInput.value = String(Date.now());
					} else {
						statusDiv.classList.add("error");
						statusDiv.textContent = text && text.length < 200 ? text.trim() : "Произошла ошибка при отправке. Попробуйте позже.";
					}
				})
				.catch((error) => {
					console.error("Error:", error);
					statusDiv.textContent = "Произошла ошибка при отправке. Проверьте интернет и попробуйте снова.";
					statusDiv.classList.add("error");
				})
				.finally(() => {
					submitBtn.disabled = false;
					submitBtn.textContent = originalBtnText;
				});
		});

		function showError(input, message) {
			input.classList.add("error");
			input.setAttribute("aria-invalid", "true");
			const group = input.closest(".form__group");
			if (!group) return;
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
				input.setAttribute("aria-invalid", "false");
				const group = input.closest(".form__group");
				if (!group) return;
				const errorSpan = group.querySelector(".form__error");
				if (errorSpan) errorSpan.textContent = "";
			});
		}
	});
});
