<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2023 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
 * Website: https://www.espocrm.com
 *
 * EspoCRM is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * EspoCRM is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with EspoCRM. If not, see http://www.gnu.org/licenses/.
 *
 * The interactive user interfaces in modified source and object code versions
 * of this program must display Appropriate Legal Notices, as required under
 * Section 5 of the GNU General Public License version 3.
 *
 * In accordance with Section 7(b) of the GNU General Public License version 3,
 * these Appropriate Legal Notices must retain the display of the "EspoCRM" word.
 ************************************************************************/

namespace tests\integration\Espo\Currency;

use Espo\Modules\Crm\Entities\Lead;
use Espo\Tools\Currency\RateService;
use Espo\Core\Currency\Rates;
use Espo\Core\Field\Currency;
use Espo\Core\InjectableFactory;
use Espo\Core\Utils\Config\ConfigWriter;

class CurrencyTest extends \tests\integration\Core\BaseTestCase
{
    public function testSetCurrencyRates(): void
    {
        $app = $this->createApplication();

        /** @var InjectableFactory $factory */
        $factory = $app->getContainer()->get('injectableFactory');

        $configWriter = $factory->create(ConfigWriter::class);

        $configWriter->set('currencyList', ['USD', 'EUR']);
        $configWriter->set('defaultCurrency', 'USD');
        $configWriter->set('baseCurrency', 'USD');
        $configWriter->set('currencyRates', [
            'EUR' => 1.2,
        ]);
        $configWriter->save();

        $service = $factory->create(RateService::class);

        $rates = Rates::fromAssoc(['EUR' => 1.3], '___');

        $service->set($rates);

        $newRates = $service->get();

        $this->assertEquals(1.3, $newRates->getRate('EUR'));
    }

    public function testDecimal1(): void
    {
        $this->getMetadata()->set('entityDefs', 'Lead', [
            'fields' => [
                'testCurrency' => [
                    'type' => 'currency',
                    'decimal' => true,
                ]
            ]
        ]);

        $this->getMetadata()->save();
        $this->getDataManager()->rebuild();
        $this->reCreateApplication();

        $value = Currency::create('10.1', 'USD')
            ->add(Currency::create('0.1', 'USD'));

        /** @var Lead $lead */
        $lead = $this->getEntityManager()->getNewEntity(Lead::ENTITY_TYPE);
        $lead->setValueObject('testCurrency', $value);
        $this->getEntityManager()->saveEntity($lead);

        /** @var Lead $lead */
        $lead = $this->getEntityManager()->getEntityById(Lead::ENTITY_TYPE, $lead->getId());

        $value = $lead->getValueObject('testCurrency');

        $this->assertInstanceOf(Currency::class, $value);
        $this->assertEquals('10.2000', $value->getAmountAsString());
        $this->assertEquals(0, $value->compare(Currency::create('10.2', 'USD')));
    }
}
