import { useTranslation } from '@/shared/i18n';

export function Hero() {
  const { t } = useTranslation();

  return (
    <section className="flex w-full max-w-2xl flex-col gap-2 text-center">
      <h1 className="font-heading text-3xl font-semibold tracking-tight text-balance sm:text-4xl">
        {t('home.hero.title')}
      </h1>
      <p className="text-base text-balance text-muted-foreground sm:text-lg">
        {t('home.hero.description')}
      </p>
    </section>
  );
}
