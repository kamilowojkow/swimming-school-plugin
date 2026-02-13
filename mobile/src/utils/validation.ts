/**
 * Form validation utilities
 */

export interface ValidationResult {
  isValid: boolean;
  error?: string;
}

/**
 * Validate email format
 */
export function validateEmail(email: string): ValidationResult {
  const trimmed = email.trim();

  if (!trimmed) {
    return { isValid: false, error: 'Email jest wymagany' };
  }

  const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
  if (!emailRegex.test(trimmed)) {
    return { isValid: false, error: 'Nieprawidłowy format email' };
  }

  return { isValid: true };
}

/**
 * Validate password
 */
export function validatePassword(password: string, minLength = 6): ValidationResult {
  if (!password) {
    return { isValid: false, error: 'Hasło jest wymagane' };
  }

  if (password.length < minLength) {
    return { isValid: false, error: `Hasło musi mieć minimum ${minLength} znaków` };
  }

  return { isValid: true };
}

/**
 * Validate password confirmation
 */
export function validatePasswordMatch(password: string, confirmPassword: string): ValidationResult {
  if (password !== confirmPassword) {
    return { isValid: false, error: 'Hasła nie są identyczne' };
  }

  return { isValid: true };
}

/**
 * Validate required field
 */
export function validateRequired(value: string, fieldName: string): ValidationResult {
  if (!value.trim()) {
    return { isValid: false, error: `${fieldName} jest wymagane` };
  }

  return { isValid: true };
}

/**
 * Validate phone number (Polish format)
 */
export function validatePhone(phone: string): ValidationResult {
  const trimmed = phone.trim();

  if (!trimmed) {
    return { isValid: true }; // Phone is optional
  }

  // Remove spaces and dashes
  const cleaned = trimmed.replace(/[\s-]/g, '');

  // Polish phone: 9 digits or +48 followed by 9 digits
  const phoneRegex = /^(\+48)?[0-9]{9}$/;
  if (!phoneRegex.test(cleaned)) {
    return { isValid: false, error: 'Nieprawidłowy format numeru telefonu' };
  }

  return { isValid: true };
}

/**
 * Validate all fields and return first error
 */
export function validateAll(validations: ValidationResult[]): ValidationResult {
  for (const validation of validations) {
    if (!validation.isValid) {
      return validation;
    }
  }
  return { isValid: true };
}

/**
 * Format phone number for display
 */
export function formatPhone(phone: string): string {
  const cleaned = phone.replace(/\D/g, '');

  if (cleaned.length === 9) {
    return `${cleaned.slice(0, 3)} ${cleaned.slice(3, 6)} ${cleaned.slice(6)}`;
  }

  if (cleaned.length === 11 && cleaned.startsWith('48')) {
    const number = cleaned.slice(2);
    return `+48 ${number.slice(0, 3)} ${number.slice(3, 6)} ${number.slice(6)}`;
  }

  return phone;
}
