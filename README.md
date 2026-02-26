# elgentos/magento2-hyva-checkout-ab-test

This extension allows you to set up an A/B test with different available Hyvä and the Luma fallback checkout.

You can configure a specific split between multiple checkouts, like the configured Hyva Checkout and the Luma fallback checkout. Or between the Hyva Checkout onepage version and the Hyva Checkout multi-step version.

## Installation

```bash
composer require elgentos/magento2-hyva-checkout-ab-test
bin/magento set:up
```

## Configuration

You can enable the extension under Stores > Configuration > Hyvä Themes > Checkout > A/B Test.

![image](https://user-images.githubusercontent.com/431360/201086503-6c54b1e0-68bd-4ec2-ab6b-e85bb52854b5.png)

## Usage

The module will switch the checkout first by using the randomizer selecting between the options that are configured.

If your magento instaltion is in development mode you it will allways select the default set checkout set in `hyva_themes_checkout/general/checkout`.
When you then add the query parameter `active_checkout_namespace` in the url you can switch manually between checkouts.
So for instance you can use `http://store.test/checkout?active_checkout_namespace=hyva`.

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
