# php-evals/core

Framework-agnostic package with:
- dataset loader (`.jsonl`)
- assertion engine
- evaluation runner
- CLI (`packages/core/bin/php-evals` in monorepo)
- testing helper (`PhpEvals\Core\Testing\EvalTestRunner`)

Default runtime contract setup:
- `ModelClient`
- `SimilarityScorer`
- pluggable assertion registry

See:
- `docs/quickstart-core.md`
- `docs/assertions.md`
- `docs/extending.md`
