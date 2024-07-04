# openbadges
#### Open Science Badges Plugin for OJS

What are open science badges? It is a system by which researchers are intended to share the raw results of their research.

We take the Anglo-Saxon example as a model, where they have been operating for years:
* [Open Science Badges enhance openness, a core value of scientific practice](https://www.cos.io/initiatives/badges)
* [Badges to Acknowledge Open Practices Wiki](https://osf.io/tvyxz/wiki/home/)

You can also see how a major academic publisher works with its Open Science Badges system:
* [Wiley Open Practices](https://authorservices.wiley.com/open-research/open-practices/index.html)



## Badges types:
* Open Data (The Open Data badge is awarded when digitally-shareable data necessary to reproduce the reported results are publicly available)
* Open Materials (The Open Materials badge is earned by making publicly available the components of the research methodology needed to reproduce the reported procedure and analysis)
* Open Analytic Code (The Open Analytic Code badge is earned by making publicly available the analytical code needed to reproduce the reported analyses)
* Open Revision


### Improvements/Features/Corrections:
* [x] Add logic for doesnt load badge block in submission when there are no badges asiggned
* [ ] Settings form to manage badges
* [x] Language implementation
  * [x] Badge image for each languaje
* [x] Security
* [ ] Option for upload/modify badge images
* [ ] Window/alert confirmation for delete database registers when module is disables
  * [ ] Review plugin lifecicle
* [x] Modify default url and add blank page
* [x] Metadata -> tag <openbadges> value: openscience </openbadges>
  * [x] Include this value in settings table (badge_settings)
* [x] Validate urls:(www.sample.web)
* [x] Add badges in submission/articles list view
* [x] Translate repo


### OJS plugin develop documentation:
* https://github.com/pkp/staticPages/tree/main