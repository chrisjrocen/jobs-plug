# JobPosting Schema Markup Documentation

## Overview

Jobs Plug automatically generates JSON-LD structured data for each job posting following [Google's JobPosting guidelines](https://developers.google.com/search/docs/appearance/structured-data/job-posting).

## Implementation

The schema markup is automatically added to the `<head>` section of single job pages via the `wp_head` hook.

## Schema Properties

### Required Properties

- **@context**: `https://schema.org`
- **@type**: `JobPosting`
- **title**: Job title from post title
- **description**: Job description from post content (stripped of HTML)
- **datePosted**: Publication date in ISO 8601 format
- **hiringOrganization**: Organization object with employer name (or site name as fallback)

### Recommended Properties

- **url**: Permalink to the job posting
- **validThrough**: Expiry date (from 'Expiry Date' meta field)
- **jobLocation**: Location object with address (from 'location' taxonomy)
- **employmentType**: Employment type (mapped from 'job_type' taxonomy)
- **baseSalary**: Salary information (from 'Salary' meta field)
- **identifier**: Unique identifier using post ID

## Employment Type Mapping

The plugin automatically maps job type taxonomy terms to schema.org employment types:

| Job Type Term | Schema Value |
|--------------|--------------|
| Full-time, Full Time, Fulltime | FULL_TIME |
| Part-time, Part Time, Parttime | PART_TIME |
| Contract, Contractor | CONTRACTOR |
| Temporary, Temp | TEMPORARY |
| Intern, Internship | INTERN |
| Volunteer | VOLUNTEER |
| Per Diem | PER_DIEM |
| Other | OTHER |

## Currency

The default currency is set to `UGX` (Uganda Shillings). To change this, use the filter:

```php
add_filter( 'jobs_plug_schema_markup', function( $schema, $post ) {
    if ( isset( $schema['baseSalary'] ) ) {
        $schema['baseSalary']['currency'] = 'USD';
    }
    return $schema;
}, 10, 2 );
```

## Customization

### Filter Hook

The schema markup can be customized using the `jobs_plug_schema_markup` filter:

```php
add_filter( 'jobs_plug_schema_markup', function( $schema, $post ) {
    // Add custom properties.
    $schema['jobBenefits'] = 'Health insurance, 401k, Remote work';

    // Modify existing properties.
    if ( isset( $schema['hiringOrganization'] ) ) {
        $schema['hiringOrganization']['logo'] = 'https://example.com/logo.png';
    }

    return $schema;
}, 10, 2 );
```

### Example Output

```json
{
  "@context": "https://schema.org",
  "@type": "JobPosting",
  "title": "Senior WordPress Developer",
  "description": "We are seeking an experienced WordPress developer...",
  "datePosted": "2025-11-20T10:30:00+00:00",
  "url": "https://example.com/job/senior-wordpress-developer/",
  "hiringOrganization": {
    "@type": "Organization",
    "name": "Example Company"
  },
  "jobLocation": {
    "@type": "Place",
    "address": {
      "@type": "PostalAddress",
      "addressLocality": "Kampala"
    }
  },
  "validThrough": "2025-12-31T23:59:59+00:00",
  "employmentType": "FULL_TIME",
  "baseSalary": {
    "@type": "MonetaryAmount",
    "currency": "UGX",
    "value": {
      "@type": "QuantitativeValue",
      "value": 50000000,
      "unitText": "YEAR"
    }
  },
  "identifier": {
    "@type": "PropertyValue",
    "name": "Example Jobs Site",
    "value": 123
  }
}
```

## Testing

Use [Google's Rich Results Test](https://search.google.com/test/rich-results) to validate your JobPosting markup.

## Google for Jobs

To maximize visibility in Google for Jobs:

1. Ensure all required fields are filled
2. Add expiry dates to all job postings
3. Include salary information when possible
4. Use accurate location data
5. Keep job descriptions detailed and well-formatted
