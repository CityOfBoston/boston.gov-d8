# BOSTON.GOV - Sanitize Read-me
## Purpose
We wish to maintain `open-source` availability of the boston.gov website, allowing Boston residents and like-minded municipalities can view, commenting on and download our Drupal source code.

These files are used as part of an `rsync` performed when synchronizing the private working repo with the public "open-source" repo.

## Methodology
While committed to the principles of the open-source community, we cannot publish secret or confidential information. This information typically can be classified environment set-ups, or integrations with third parties.

We do not publish copies of our database which contains our site content, and possibly data we have an obligation to protect -for example for data privacy reasons.

The santitize process occurs in a GithubAction found in D10-Publish.yml

## Public/Private Repo Rationale
We maintain a private working repo:
- So that any confidential/sensitive settings which are accidentially committed do not make their way into a public repo.
- To keep the public repo somewhat managed and "clean".
- To keep the public repo as un-complicated as possible.
- To keep the public repo in line with the production code committed and deployed to Acquia.
- To keep the commit messages and language professional and consistent (templated) between deploys.
- So that release notes and other communications can be formatted suitable for general consumption.
