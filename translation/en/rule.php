<?php

return [
    // General Rules
    'insertRecordSuccess' => 'Data has been saved successfully.',
    'insertRecordFailed' => 'Failed to save data.',
    'updateRecordSuccess' => 'Data has been updated successfully.',
    'updateRecordFailed' => 'Failed to update data.',
    'deleteRecordSuccess' => 'Data has been deleted successfully.',
    'deleteRecordFailed' => 'Failed to delete data.',
    'badRequest' => 'Bad Request.',
    'validationFailed' => 'Failed to save data.',
    'fieldValidationFailed' => 'Field validation failed.',
    'paramsEmpty' => 'At least one input must be provided except "id" to update data.',
    'dataNotFound' => 'Data not found.',
    'valueExists' => 'Value already exist. Please use another value.',
    'valueAlreadyExists' => '{field} value already exist. Please use another value.',
    'fieldNotValid' => 'Field {field} not valid.',
    'fieldDataNotFound' => '{field} data not found.',
    'deletedStatusChanged' => 'You do not have permission to change the status from {status} to another status. Admin rights are required.',

    // Status Update Rules
    'disallowedStatusUpdate' => 'Cannot change status because data already {status}',
    'cannotChangeStatus' => 'Cannot change status from {status} to {newStatus}.',
    
    // Category Rules
    'codeContainZ' => 'Code cannot contain the letter "Z".',
    'codeOnlyAlphaAtoY' => 'Code only alphabet characters A-Y are allowed.',
    'codeAlreadyExist' => 'Code {value} already exists.',
    'idCannotBlank' => 'Id cannot be blank.',
    'idMustInteger' => 'Id must be an integer.',

    // Delivery Order Rules
    'productNameString' => 'Product [{index}].name must be a string with a maximum length of 500 characters.',
    
    // Inventory Rules
    'picMustArray' => 'PIC must be an array.',
    'picNameString' => 'Each PIC name must be a string with a maximum length of 255 characters.',
    'picPhoneValidation' => 'Each PIC phone must start with 62 and be between 9 and 15 digits.',
    'picNoteValidation' => 'Each note for PIC must be a string with a maximum length of 255 characters.',
    'picAllowedField' => 'Each pic can only contain the fields {field}.',

    // Invetory Log Rules
    'quantityMustInteger' => 'Quantity must be an integer.',
    'productDataNotFound' => 'Product data with ID {id} not found.',
    'productIdMustInteger' => 'Product ID must be an integer.',
    'productMustArray' => 'Product must be an array.',
    
    // Attendance Rules
    'dateCannotBlank' => 'Date cannot be blank.',
    'dateAlreadyPast' => 'The date has passed.',
];

