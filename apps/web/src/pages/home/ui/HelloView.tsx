import { useTranslation } from '@/shared/i18n';
import { Card, CardContent } from '@/shared/ui/card';

type HelloViewProps = {
  email: string;
};

export function HelloView({ email }: HelloViewProps) {
  const { t } = useTranslation();

  return (
    <main>
      <Card>
        <CardContent>{t('hello', { email })}</CardContent>
      </Card>
    </main>
  );
}
