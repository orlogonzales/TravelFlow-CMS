/**
 * TF Design System - Tokens Base (Fase 0D)
 *
 * Espacio de definición de tokens para colores, tipografía, espaciado y utilidades.
 * Los componentes oficiales (TfButton, TfModal, TfTable, TfSkeleton, etc.) se incorporarán
 * en sus respectivas fases sin crear componentes ficticios por adelantado.
 */

export interface TfDesignTokens {
  readonly brand: {
    readonly primary: string;
    readonly dark: string;
    readonly light: string;
  };
  readonly spacing: {
    readonly base: number; // 4px / 8px scale
  };
  readonly transitions: {
    readonly default: string;
  };
}

export const defaultTokens: TfDesignTokens = {
  brand: {
    primary: '#0d6efd',
    dark: '#1e293b',
    light: '#f8fafc',
  },
  spacing: {
    base: 8,
  },
  transitions: {
    default: '150ms ease-in-out',
  },
};
