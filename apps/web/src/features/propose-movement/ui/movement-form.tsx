import { useState } from 'react';
import type { FormEvent } from 'react';

import { AREAS, useCategories } from '@/entities/movement';
import type { Area, Movement, MovementDraftInput } from '@/entities/movement';
import { useTranslation } from '@/shared/i18n';
import { Button, Input, Label, Select, Textarea } from '@/shared/ui';

interface MovementFormProps {
  initial?: Movement;
  pending: boolean;
  serverError: string | null;
  fieldErrors: Record<string, string>;
  onSubmit: (input: MovementDraftInput) => void;
}

export function MovementForm({
  initial,
  pending,
  serverError,
  fieldErrors,
  onSubmit,
}: MovementFormProps) {
  const { t } = useTranslation();
  const categories = useCategories();
  const [title, setTitle] = useState(initial?.title ?? '');
  const [description, setDescription] = useState(initial?.description ?? '');
  const [category, setCategory] = useState(initial?.category ?? '');
  const [area, setArea] = useState<Area | ''>(initial?.area ?? '');
  const [location, setLocation] = useState(initial?.location ?? '');
  const [localErrors, setLocalErrors] = useState<Record<string, string>>({});

  const errors: Record<string, string> = { ...fieldErrors, ...localErrors };
  const international = area === 'international';

  const handleSubmit = (event: FormEvent<HTMLFormElement>) => {
    event.preventDefault();

    const missing: Record<string, string> = {};
    const required = t('movements.form.required');

    if (title.trim() === '') missing.title = required;
    if (category === '') missing.category = required;
    if (area === '') missing.area = required;
    if (!international && location.trim() === '') missing.location = required;

    setLocalErrors(missing);

    if (Object.keys(missing).length > 0 || area === '') {
      return;
    }

    onSubmit({
      title: title.trim(),
      description,
      category,
      area,
      location: international ? null : location.trim(),
    });
  };

  const fieldError = (field: string) =>
    errors[field] !== undefined ? (
      <p role="alert" className="text-sm text-destructive">
        {errors[field]}
      </p>
    ) : null;

  return (
    <form noValidate className="flex w-full flex-col gap-4" onSubmit={handleSubmit}>
      <div className="flex flex-col gap-2">
        <Label htmlFor="movement-title">{t('movements.form.titleLabel')}</Label>
        <Input
          id="movement-title"
          value={title}
          maxLength={200}
          aria-invalid={errors.title !== undefined}
          onChange={(event) => setTitle(event.target.value)}
        />
        {fieldError('title')}
      </div>

      <div className="flex flex-col gap-2">
        <Label htmlFor="movement-category">{t('movements.form.categoryLabel')}</Label>
        <Select
          id="movement-category"
          value={category}
          aria-invalid={errors.category !== undefined}
          onChange={(event) => setCategory(event.target.value)}
        >
          <option value="">{t('movements.form.categoryPlaceholder')}</option>
          {(categories.data ?? []).map((option) => (
            <option key={option.id} value={option.id}>
              {t(`movements.category.${option.id}`, { defaultValue: option.id })}
            </option>
          ))}
        </Select>
        {fieldError('category')}
      </div>

      <div className="flex flex-col gap-2">
        <Label htmlFor="movement-area">{t('movements.form.areaLabel')}</Label>
        <Select
          id="movement-area"
          value={area}
          aria-invalid={errors.area !== undefined}
          onChange={(event) => setArea(event.target.value as Area | '')}
        >
          <option value="">{t('movements.form.areaPlaceholder')}</option>
          {AREAS.map((option) => (
            <option key={option} value={option}>
              {t(`movements.area.${option}`)}
            </option>
          ))}
        </Select>
        {fieldError('area')}
      </div>

      {!international && (
        <div className="flex flex-col gap-2">
          <Label htmlFor="movement-location">{t('movements.form.locationLabel')}</Label>
          <Input
            id="movement-location"
            value={location}
            placeholder={t('movements.form.locationPlaceholder')}
            aria-invalid={errors.location !== undefined}
            onChange={(event) => setLocation(event.target.value)}
          />
          {fieldError('location')}
        </div>
      )}

      <div className="flex flex-col gap-2">
        <Label htmlFor="movement-description">{t('movements.form.descriptionLabel')}</Label>
        <Textarea
          id="movement-description"
          value={description}
          maxLength={20000}
          aria-invalid={errors.description !== undefined}
          onChange={(event) => setDescription(event.target.value)}
        />
        {fieldError('description')}
      </div>

      {serverError !== null && (
        <p role="alert" className="text-sm text-destructive">
          {serverError}
        </p>
      )}

      <Button type="submit" disabled={pending}>
        {t('movements.form.save')}
      </Button>
    </form>
  );
}
