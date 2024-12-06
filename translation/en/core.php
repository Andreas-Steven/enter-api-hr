<?php

return [
    // General Rules
    'badRequest' => 'Bad Request.',
    'emptyParams' => 'At least one input must be provided except "id" to update data.',
    'dataNotFound' => 'Data not found.',
    'exceptionOccured' => 'An exception has occurred.',
    'unauthorizedAccess' => 'Unauthorized access.',
    'serverError' => 'Server error.',

    // General Records Rules
    'createRecordSuccess' => 'Data has been saved successfully.',
    'createRecordFailed' => 'Failed to save data.',
    'updateRecordSuccess' => 'Data has been updated successfully.',
    'updateRecordFailed' => 'Failed to update data.',
    'deleteRecordSuccess' => 'Data has been deleted successfully.',
    'deleteRecordFailed' => 'Failed to delete data.',
    'noRecordDeleted' => 'Failed, Record already deleted.',
    'noRecordUpdated' => 'Failed, no record updated.',

    // Field Validation Rules
    'required' => 'Field {field} cannot be blank.',
    'integer' => 'Field {field} must be an integer.',
    'validationFailed' => 'Field validation failed.',
    'invalidField' => 'Field {field} not a valid request parameter.',
    'fieldDataNotFound' => '{field} data not found.',
    'extraFields' => 'Extra fields found in {label}: {fields}. Allowed fields: {allowed}.',
    'missingFields' => 'Missing required fields: {fields}.',
    'nullField' => '{label} field: {fields} is cannot be null or empty.',
    'allowedField' => '{field} can only contain the fields {value}.',

    // General Pagination Rules
    'pageMustBeGreaterThanZero' => 'Page must be greater than 0.',
    
    // Status Update Rules
    'disallowedStatusUpdate' => 'Cannot change status because data already {value}.',
    'cannotChangeStatus' => 'Cannot change status from {value} to {newValue}.',
    'deletedStatusChanged' => 'You do not have permission to change the status from {value} to another status. Admin rights are required.',
    
    // Superadmin Rights Rules
    'superadminOnly' => 'You do not have permission to perform this action.',
    'updatePermission' => 'You do not have permission to update the {label} of this {tableName} because it is referenced in other data.',

];

