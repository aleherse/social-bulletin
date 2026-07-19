import Markdown from 'react-markdown';

/**
 * FR-008: react-markdown builds React elements from the markdown AST and
 * never injects raw HTML (no rehype-raw), so descriptions cannot execute
 * scripts or embed unsafe markup.
 */
export function MovementDescription({ markdown }: { markdown: string }) {
  return (
    <div className="flex flex-col gap-2 text-sm [&_a]:underline [&_code]:font-mono [&_h1]:text-lg [&_h1]:font-semibold [&_h2]:text-base [&_h2]:font-semibold [&_h3]:font-semibold [&_li]:ml-4 [&_ol]:list-decimal [&_ul]:list-disc">
      <Markdown>{markdown}</Markdown>
    </div>
  );
}
