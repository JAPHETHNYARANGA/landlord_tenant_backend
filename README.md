

Landlord Table: Does not need a property ID because it can have multiple properties associated with it.
Properties Table: Holds the landlord_id to establish which landlord owns each property.
Flexibility: This design allows landlords to exist without properties and easily manage multiple properties in the future.

Create a Landlord:
If you have a landlord, create them first.

Create Properties:
After the landlord is created, you can create one or more properties associated with that landlord.

Create Tenants:
You can create tenants at any time, but when assigning a tenant to a property, ensure that the property exists.