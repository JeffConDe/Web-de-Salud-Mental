export function apiErrorMessage(error: unknown, fallback: string): string {
  if (typeof error !== 'object' || error === null || !('error' in error)) {
    return fallback;
  }

  const body = error.error;
  if (typeof body !== 'object' || body === null) {
    return fallback;
  }

  if ('errors' in body && typeof body.errors === 'object' && body.errors !== null) {
    const firstError = Object.values(body.errors).flat().find(
      (value): value is string => typeof value === 'string',
    );
    if (firstError) {
      return firstError;
    }
  }

  return 'message' in body && typeof body.message === 'string' ? body.message : fallback;
}
