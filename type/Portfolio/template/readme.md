# Portfolio Template

## Data
* With use of the `portfolio_data` class in templates course data will be automatically retrieved based on checkbox custom fields.
* Custom fields will be selected based on their name starting with the `port_` prefix.
* A course designated to display on portfolios will then have the custom field checkbox for the category it falls into checked.
* If the first word of a custom field description is "required" the section will always be displayed. If no completed courses are present in the section a no courses completed message will be displayed.
* Any additional description after "required" word will be output under the section header.
* While developing a portfolio dummy data can be populated for the sections be appending `&debug` to the PDF output url.

## Using The Templates
1. Create a new directory within `mod/certificate/type/` following the `portfolio_<acronym>` identifier pattern. e.g. `portfolio_gch` for Gold Coast Health.
2. Copy both `certificate.php` and `portfolio_output.php` from this directory to your newly created directory.
3. Run a find and replace in both files to replace `portfolio_temp` with your identifier which should match the directory name. e.g. `portfolio_gch`.
4. Add new language strings to `mod/certificate/lang/en/certificate.php` for your portfolio:
   1. Type string to determine the name displayed in the templates list. e.g. `typeportfolio_gch`.
   2. Main language keys block. This can be copied from an existing portfolio type which can be found under a comment like such; `type/portfolio_gch`. Be sure to change the identifier in the language keys to match yours.

## Implementation
1. Add a new Certificate module on a course and select the Certificate Type option under Design Options that matches your type language string.
2. Configure the language keys block created in the above steps. This will include site specific names and colour values as well as general page texts.
3. Open the certificate PDF by clicking the _Get your certificate_ button when viewing the Certificate course module. This will give you a view of the base layout with your language strings applied.
4. Start your customisation by opening your `certificate.php` file and configuring both `$offsets->x` and `$offsets->y`.<br> These values will determine the base offset of every page on their given axis which can be useful to fit within page boarders when using a background border image.
5. Next, open your `portfolio_output.php` file and navigating to the `output_cover_page()` function. This function controls the output of the first page of your portfolio and is designed to have a more significant output.<br> **Note:** You may notice the course list overlapping content while designing your cover page. This is expected and will be addressed in an upcoming step.
   1. Text colour is set using the `$this->apply_x_colour()` functions and will pull the hex colour configured in your language strings.
   2. Language strings in your portfolio's language block will be pulled via the `$this->get_string(<string>)` function. The string parameter is the language string less your identifier. e.g. `portfolio_gch_title` is retrieved with `$this->get_string('title')` in `portfolio_gch`.
   3. Text output is primarily achieved with the `$this->output_text()` function which will output text at a given offset from your base `x` and `y` offset values.
      1. `$this->output_text_static()` may also be used to output text at a fixed location rather than relative to the base offsets.
   4. The TCPDF instance is also available for direct output calls as `$this->pdf`.
6. You may have noticed that the generated course list overlaps or is otherwise incorrectly spaced with your custom elements. This is what we will adjust now.<br> There are two functions in `portfolio_output.php` that control the layout of the course list. These are:
   1. `page_rows()` which determines the number of course output rows a regular full page supports.
   2. `cover_offset()` which sets the starting `y` offset for the course list on the cover page to accommodate custom elements.
7. Now that the cover page is configured you can start adjusting the positioning of various page elements by editing the various `$offsets` properties in `certificate.php`. The main offsets to take note of are:
   1. `row_indent`: Indentation amount of a course output row under its header.
   2. `date_y`: Y position of the _"Printed on {date}"_ output.
   3. `page_num_y`: Y position of the page number.
   4. `site_service_y`: Y position of the site service footer. This is the _"Presented by"_ element.
8. Further customisation can be achieved by overriding functions from the parent class. This allows changes as simple as using a different colour to completely changing the output of a page element.<br> The example below shows how you would change the language colour of the page number from `colour_minor` to `colour_base`. Note the default value for the `$colour` parameter:<br>
   1. ```php
      protected function output_page_number(string $colour = portfolio_colour::BASE): void {
        parent::output_page_number($colour);
      }
      ```
   2. When overriding base functions it is advised to make as much use as possible of the utility and output functions from the base class.