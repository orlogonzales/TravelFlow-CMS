/**
 * Composable HTTP Oficial de TravelFlow CMS (useApi)
 *
 * Arquitectura:
 * - Fetch API nativo tipado en TypeScript.
 * - Sin dependencias externas (Axios y jQuery descartados).
 * - Autenticación stateful basada en cookies de sesión HttpOnly y tokens CSRF de Laravel Sanctum.
 * - Sin almacenamiento de credenciales o tokens en localStorage ni sessionStorage.
 */

export interface ApiErrorResponse {
  message?: string;
  errors?: Record<string, string[]>;
  success?: boolean;
}

export interface ApiResponse<T = any> {
  data: T;
  status: number;
  ok: boolean;
}

export class ApiError extends Error {
  public status: number;
  public data: ApiErrorResponse;

  constructor(status: number, data: ApiErrorResponse) {
    super(data.message || `Error HTTP ${status}`);
    this.name = 'ApiError';
    this.status = status;
    this.data = data;
  }
}

/**
 * Extrae una cookie por su nombre del encabezado document.cookie del navegador.
 */
function getCookie(name: string): string | null {
  if (typeof document === 'undefined') {
    return null;
  }
  const match = document.cookie.match(new RegExp('(^|;\\s*)(' + name + ')=([^;]*)'));
  return match ? decodeURIComponent(match[3]) : null;
}

/**
 * Realiza la solicitud inicial de cookie CSRF a Laravel Sanctum (/sanctum/csrf-cookie).
 */
export async function initializeCsrf(): Promise<void> {
  await fetch('/sanctum/csrf-cookie', {
    method: 'GET',
    headers: {
      Accept: 'application/json',
      'X-Requested-With': 'XMLHttpRequest',
    },
    credentials: 'same-origin',
  });
}

export interface ApiRequestOptions extends RequestInit {
  params?: Record<string, string | number | boolean>;
}

export function useApi() {
  /**
   * Ejecuta una petición HTTP contra el backend de TravelFlow CMS.
   */
  async function request<T = any>(endpoint: string, options: ApiRequestOptions = {}): Promise<T> {
    const { params, headers = {}, ...customConfig } = options;

    let url = endpoint;
    if (params) {
      const searchParams = new URLSearchParams();
      Object.entries(params).forEach(([key, val]) => {
        if (val !== undefined && val !== null) {
          searchParams.append(key, String(val));
        }
      });
      const queryString = searchParams.toString();
      if (queryString) {
        url += (url.includes('?') ? '&' : '?') + queryString;
      }
    }

    const xsrfToken = getCookie('XSRF-TOKEN');

    const defaultHeaders: Record<string, string> = {
      Accept: 'application/json',
      'X-Requested-With': 'XMLHttpRequest',
    };

    if (xsrfToken) {
      defaultHeaders['X-XSRF-TOKEN'] = xsrfToken;
    }

    if (!(customConfig.body instanceof FormData) && customConfig.body) {
      defaultHeaders['Content-Type'] = 'application/json';
    }

    const config: RequestInit = {
      method: customConfig.method || 'GET',
      headers: {
        ...defaultHeaders,
        ...(headers as Record<string, string>),
      },
      credentials: 'same-origin',
      ...customConfig,
    };

    const response = await fetch(url, config);

    // Si ocurre un error 419 (CSRF Token Mismatch / Expired), reintentar una sola vez tras renegociar CSRF
    if (response.status === 419) {
      await initializeCsrf();
      const updatedXsrf = getCookie('XSRF-TOKEN');
      if (updatedXsrf) {
        (config.headers as Record<string, string>)['X-XSRF-TOKEN'] = updatedXsrf;
      }
      const retryResponse = await fetch(url, config);
      return handleResponse<T>(retryResponse);
    }

    return handleResponse<T>(response);
  }

  async function handleResponse<T>(response: Response): Promise<T> {
    const contentType = response.headers.get('content-type') || '';
    const isJson = contentType.includes('application/json');

    const data = isJson ? await response.json() : await response.text();

    if (!response.ok) {
      const errorData: ApiErrorResponse = isJson ? data : { message: data || response.statusText };
      throw new ApiError(response.status, errorData);
    }

    return data as T;
  }

  function get<T = any>(endpoint: string, options?: ApiRequestOptions): Promise<T> {
    return request<T>(endpoint, { ...options, method: 'GET' });
  }

  function post<T = any>(endpoint: string, body?: any, options?: ApiRequestOptions): Promise<T> {
    return request<T>(endpoint, {
      ...options,
      method: 'POST',
      body: body instanceof FormData ? body : JSON.stringify(body),
    });
  }

  function put<T = any>(endpoint: string, body?: any, options?: ApiRequestOptions): Promise<T> {
    return request<T>(endpoint, {
      ...options,
      method: 'PUT',
      body: body instanceof FormData ? body : JSON.stringify(body),
    });
  }

  function del<T = any>(endpoint: string, options?: ApiRequestOptions): Promise<T> {
    return request<T>(endpoint, { ...options, method: 'DELETE' });
  }

  return {
    request,
    get,
    post,
    put,
    delete: del,
    initializeCsrf,
  };
}
