Look at my codebase and understand the database schema, especially 
tables/models related to users, payments/orders, and packages/plans.

Then build me a simple admin analytics dashboard that shows:

1. Monthly revenue (total cash collected per month, grouped by package type)
2. Active paid users (users with a currently active/non-expired package)
3. Repurchase rate (% of users whose package expired in the last 30/60/90 
   days who bought again)
4. New vs returning buyers per month
5. Revenue by package type breakdown (founder, 3mo, 6mo, 12mo)
6. Upcoming expirations (how many users expire in the next 30/60/90 days - 
   this is your "churn risk" list)

Use the existing tech stack. Make it a protected /admin/analytics route.
Keep it simple — tables and basic charts are fine.

If any of the mentioned tasks is already been done currently in filament dashboard, state it so I can discuss with my thinking model.