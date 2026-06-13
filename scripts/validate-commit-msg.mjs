#!/usr/bin/env node
import fs from 'node:fs';

const file = process.argv[2];
const message = fs.readFileSync(file, 'utf8').trim().split('\n')[0] ?? '';
const conventional = /^(?:[A-Z][A-Z0-9]+-\d+\s+)?(feat|fix|docs|style|refactor|perf|test|build|ci|chore|revert)(?:\([a-z0-9-]+\))?!?: .{1,}$/;

if (!conventional.test(message)) {
  console.error('Commit message must follow Conventional Commits, optionally prefixed by a task ID.');
  console.error('Example: SB-123 feat(api): add registration endpoint');
  process.exit(1);
}
