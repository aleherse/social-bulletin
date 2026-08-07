# Agents should ignore this file

# extract rule skill

Create a skill to extract rules from code changes that follows this steps

## Step 1: Establish the scope

If there are uncommited changes in the current repository those files are the scope of this skill

Otherwise, ask for a specific commit or a number of commits behind the current branch, by default propose the latest commit.

## Step 2: Read the changes

Read the files from the scope and understand the changes that have been introduced

## Step 3: Distill the rule

Try to extract the rule or rules behind the reason of the changes, it should belong to one of the categories from this non-exhaustive tree:

- Infrastructure:
    - Deployment
    - CI
- Scaffolding:
    - Docker
    - Folders
- Application:
    - Framework
    - Security
- Domain
- Test
    - E2E
    - Unit
    - Performance
    - Integration
- Database
- Documentation

Do not add new categories but consider adding new subcategories if you see them fit.

If you find rules that might belong to more than one subcategory, summarise the rules and ask for the relevant subcategory. Rules that do not belong to the subcategory should be memorised and left aside, at the end of the flow step 3 could be restarted just for those rules.

Expose the reason behind the rule and wait for user confirmation

## Step 4: Contrast them with existing rules

Rules are written as Markdown files inside the `docs/rules/<category>/<subcategory>.md` file

Read the existing rules from the file

Check if distilled rules add, update or conflict with the existing rules

## Step 5: Formalise the rule

Write the rule(s) in the specified file using the following template

```
**WHEN** <condition that triggers the rule>
**THEN** <rule that should guide the agent>

*Example:* 
    <specific rule application examples>

```

Example section is optional and should be added if the rule needs clarification, use lists, tables or code snippets to make it easier to read

Ask for user feedback and confirmation

# Step 6: Write the rule

Write the changes to the file

If other rules were memorised restart the process with them at step 3, if not you are done
