export const AREAS = [
  'international',
  'national',
  'state',
  'province',
  'region',
  'municipality',
  'neighborhood',
] as const;

export type Area = (typeof AREAS)[number];

export type MovementStatus = 'draft' | 'proposed' | 'published';

export interface Movement {
  id: string;
  title: string;
  description: string;
  category: string;
  area: Area;
  location: string | null;
  status: MovementStatus;
  createdAt: string;
  updatedAt: string;
}

export interface MovementDraftInput {
  title: string;
  description: string;
  category: string;
  area: Area;
  location: string | null;
}

export interface Category {
  id: string;
}
