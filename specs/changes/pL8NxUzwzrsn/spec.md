# Spec: Add a Hero section to the homepage

## Problem
The homepage opens straight onto the sign-in form. A visitor gets no statement of what Social Bulletin is for before being asked for an email address.

## Goals
- The sign-in form's own heading no longer repeats the Hero's headline.
- Every visitor to the homepage sees a prominent Hero section stating the product's name and purpose.
- The Hero is the first content a visitor reads on the page, before the sign-in or signed-in content.

## Non-Goals
- Images, buttons, calls to action or other content in the Hero beyond the two texts below.
- Changes to the sign-in form beyond its heading wording, the signed-in greeting or the sign-out behaviour.
- Languages other than the ones the homepage already supports.

## User Stories
- As a first-time visitor, I want to see a headline and a one-line description of Social Bulletin when the homepage opens so that I understand what the service offers before I register. [P1]
- As a returning, signed-in visitor, I want the same Hero to remain visible so that the homepage looks consistent whether or not I am signed in. [P2]
- As a visitor using a phone or a narrow window, I want the Hero text to stay fully readable without horizontal scrolling so that the page works on any device. [P2]

## Success Criteria
- The homepage shows the headline "Welcome to Social Bulletin" and, beneath it, the text "Your place to stay up to date with the latest social movements events".
- The headline is the page's main heading, and the description reads as its supporting text.
- Both texts are visible without scrolling on a typical desktop and phone viewport.
- The Hero appears while the page is loading, for a visitor who is not signed in and for a signed-in visitor.
- No horizontal scrolling is needed at a viewport width of 320 px.
- The sign-in form no longer repeats the Hero's headline: its heading reads "Register or sign in", and its description, fields and button are unchanged.
- Existing homepage behaviour (registration, sign-in, greeting, sign-out) is otherwise unchanged.

## Assumptions
- The card's "Soclal" is a typo for "Social" — the product is named Social Bulletin everywhere else, so the correct spelling is used.
- The description text is used exactly as written, without added punctuation or rewording — the card gives the content verbatim.
- The Hero is shown to all visitors, signed in or not — the card places it on the homepage without restricting its audience.
- The Hero sits above the existing homepage content rather than replacing it — the card asks to add a section.
- The texts are shown in English only, matching the language the homepage currently ships.
- The task-focused form heading is a default wording ("Register or sign in") chosen at planning, as the clarification answer left the exact text to planning.

## Clarifications

- **1.** What should happen to the sign-in form's own heading? → a) Reword it to a task-focused heading; the Hero carries the welcome *(answered by a person)*

## Open Questions

None.
