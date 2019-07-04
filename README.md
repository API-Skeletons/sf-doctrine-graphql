Doctrine GraphQL for Symfony
============================

This library uses Doctrine native traversal of related objects to provide full GraphQL
querying of entities and all related fields and entities.
Entity metadata is introspected and is therefore Doctrine data driver agnostic.
Data is collected with hydrators thereby
allowing full control over each field using hydrator filters, strategies and naming strategies.
Multiple object managers are supported. Multiple hydrator configurations are supported.
Works with [GraphiQL](https://github.com/graphql/graphiql).
