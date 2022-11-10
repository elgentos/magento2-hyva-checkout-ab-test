# elgentos/magento2-hyva-checkout-ab-test

This extension allows you to set up an A/B test with different available Hyvä and the Luma fallback checkout.

It will randomly do a 50% split between the configured Hyva Checkout and the Luma fallback checkout.

## Installation

```bash
composer require elgentos/magento2-hyva-checkout-ab-test
bin/magento set:up
```

## Configuration

None, as of now.

## Reports

You can check the progress of the A/B test by running this query;

```sql
SET SQL_MODE='';
SELECT quote.active_checkout_namespace as checkout, COUNT(quote.entity_id) as quotes, COUNT(sales_order.quote_id) as orders, (COUNT(sales_order.quote_id) / COUNT(quote.entity_id)*100) as conversion_percentage
FROM quote
         LEFT JOIN sales_order ON quote.entity_id = sales_order.quote_id AND sales_order.state IN ('completed', 'processing')
WHERE quote.active_checkout_namespace IS NOT NULL
GROUP BY quote.active_checkout_namespace;
```

If you want to see the results in the Magento admin, you can install [degdigital/magento2-customreports](https://github.com/degdigital/magento2-customreports) and add the above query. Be sure to leave out the SQL_MODE part (Magento does this for you) and leave no trailing/leading white lines.

The report will look like this;

![image](https://user-images.githubusercontent.com/431360/200922747-6be0d031-3156-40cd-a989-c8399d4daae3.png)
