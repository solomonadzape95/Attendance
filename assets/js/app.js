/**
 * Attendance System - Client-side Validation
 * Character restrictions and form validation
 */

document.addEventListener("DOMContentLoaded", function () {
	// Validation patterns
	const patterns = {
		fullName: /^[a-zA-Z\s\-']{2,100}$/,
		username: /^[a-zA-Z0-9_]{3,50}$/,
		password: /^(?=.*[A-Za-z])(?=.*\d)[A-Za-z\d@$!%*#?&]{6,50}$/,
		studentName: /^[a-zA-Z\s\-']{2,100}$/,
		rollNo: /^[a-zA-Z0-9\-\/]{1,50}$/,
		course: /^[a-zA-Z0-9\s\-]{2,50}$/,
	};

	// Error messages
	const errorMessages = {
		fullName:
			"Full name: only letters, spaces, hyphens, apostrophes (2-100 chars)",
		username: "Username: only letters, numbers, underscore (3-50 chars)",
		password:
			"Password: 6-50 chars with at least one letter and one number",
		studentName:
			"Name: only letters, spaces, hyphens, apostrophes (2-100 chars)",
		rollNo: "Roll No: only letters, numbers, hyphens, slashes (1-50 chars)",
		course: "Course: only letters, numbers, spaces, hyphens (2-50 chars)",
	};

	// Validate a field and show/hide error
	function validateField(input, patternKey) {
		const value = input.value.trim();
		const isValid = patterns[patternKey].test(value);

		// Remove existing error
		const existingError =
			input.parentNode.querySelector(".validation-error");
		if (existingError) {
			existingError.remove();
		}

		if (!isValid && value.length > 0) {
			input.classList.add("is-invalid");
			input.classList.remove("is-valid");

			const errorDiv = document.createElement("div");
			errorDiv.className = "validation-error text-danger small mt-1";
			errorDiv.textContent = errorMessages[patternKey];
			input.parentNode.appendChild(errorDiv);
		} else if (value.length > 0) {
			input.classList.add("is-valid");
			input.classList.remove("is-invalid");
		} else {
			input.classList.remove("is-valid", "is-invalid");
		}

		return isValid;
	}

	// Restrict input to allowed characters in real-time
	function restrictInput(input, allowedPattern) {
		input.addEventListener("input", function (e) {
			const cursorPos = this.selectionStart;
			const originalValue = this.value;
			const newValue = originalValue.replace(allowedPattern, "");

			if (originalValue !== newValue) {
				this.value = newValue;
				// Adjust cursor position
				this.setSelectionRange(cursorPos - 1, cursorPos - 1);
			}
		});
	}

	// Admin form validation
	const adminForm = document.getElementById("adminForm");
	if (adminForm) {
		const fullNameInput = adminForm.querySelector(
			'input[name="full_name"]',
		);
		const usernameInput = adminForm.querySelector('input[name="username"]');
		const passwordInput = adminForm.querySelector('input[name="password"]');

		if (fullNameInput) {
			restrictInput(fullNameInput, /[^a-zA-Z\s\-']/g);
			fullNameInput.addEventListener("blur", () =>
				validateField(fullNameInput, "fullName"),
			);
		}
		if (usernameInput) {
			restrictInput(usernameInput, /[^a-zA-Z0-9_]/g);
			usernameInput.addEventListener("blur", () =>
				validateField(usernameInput, "username"),
			);
		}
		if (passwordInput) {
			passwordInput.addEventListener("blur", () =>
				validateField(passwordInput, "password"),
			);
		}
	}

	// Student form validation
	const studentNameInputs = document.querySelectorAll(
		'input[name="student_name"]',
	);
	const rollNoInputs = document.querySelectorAll('input[name="roll_no"]');
	const courseInputs = document.querySelectorAll('input[name="course"]');

	studentNameInputs.forEach((input) => {
		restrictInput(input, /[^a-zA-Z\s\-']/g);
		input.addEventListener("blur", () =>
			validateField(input, "studentName"),
		);
	});

	rollNoInputs.forEach((input) => {
		restrictInput(input, /[^a-zA-Z0-9\-\/]/g);
		input.addEventListener("blur", () => validateField(input, "rollNo"));
	});

	courseInputs.forEach((input) => {
		restrictInput(input, /[^a-zA-Z0-9\s\-]/g);
		input.addEventListener("blur", () => validateField(input, "course"));
	});

	// Date validation for attendance
	const dateInput = document.querySelector('input[name="date"]');
	if (dateInput) {
		dateInput.addEventListener("change", function () {
			const selectedDate = new Date(this.value);
			const minDate = new Date(this.min);
			const maxDate = new Date(this.max);

			if (selectedDate < minDate || selectedDate > maxDate) {
				this.classList.add("is-invalid");
				alert("Please select a date within the allowed range.");
				this.value = this.max; // Reset to today
			} else {
				this.classList.remove("is-invalid");
				this.classList.add("is-valid");
			}
		});
	}

	// Form submission validation
	document.querySelectorAll("form").forEach((form) => {
		form.addEventListener("submit", function (e) {
			let isValid = true;

			// Check all required fields with patterns
			this.querySelectorAll("input[pattern]").forEach((input) => {
				const pattern = new RegExp(input.pattern);
				if (!pattern.test(input.value.trim())) {
					input.classList.add("is-invalid");
					isValid = false;
				}
			});

			if (!isValid) {
				e.preventDefault();
				alert(
					"Please correct the highlighted fields before submitting.",
				);
			}
		});
	});

	console.log("Attendance System validation loaded.");
});
