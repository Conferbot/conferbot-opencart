# Web widget parity: OpenCart

The widget loads its look and behaviour from the Conferbot dashboard, so a plugin only has to load it and pass the store's data. Done in this plugin:

- the loader, as the widget runs it today
- the signed-in customer as the visitor context, with the identity verification hash made server side
- sales tracking
- README setup steps

## Left

- **OpenCart 4.** The plugin targets OpenCart 3.0.x; the README says OC4 isn't supported yet. OC4 needs its own build:
  - the `extension/conferbot/{admin,catalog}` layout
  - namespaced controllers
  - events registered through `model_setting_event`
  - the `.ocmod.zip` install
  - Keep the OC3 build working; tests are in `tests/`.
