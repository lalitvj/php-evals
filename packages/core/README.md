# php-evals/core

Framework-agnostic core package for dataset-driven eval runs.

Includes:
- dataset loader (`.jsonl`)
- assertion engine
- evaluation runner
- CLI (`packages/core/bin/php-evals` in monorepo)
- testing helper (`PhpEvals\Core\Testing\EvalTestRunner`)
- OpenAI embedding scorer and judge adapters

Default scoring mode is lightweight/local. For higher-trust scoring, configure:
- `similarity_scorer => 'openai_embeddings'`
- `judge_client => 'openai'`

See:
- `docs/quickstart-core.md`
- `docs/assertions.md`
- `docs/extending.md`
