# php-gettext utilities

## Commands

### `merge-conflicts`

Gives the ability of choosing one side of a translation on a conflict for the
whole file, if the file was merged using https://github.com/beck/git-po-merge

```bash
bin/console merge-conflicts [base|ours|theirs] [input.po] [output.po]
```

Pass a `--fuzzy` to keep the merged translation as fuzzy, if it was in that state.
```bash
bin/console merge-conflicts base input.po output.po --fuzzy
```