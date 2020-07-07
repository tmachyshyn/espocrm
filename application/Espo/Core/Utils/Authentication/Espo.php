<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2020 Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
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

namespace Espo\Core\Utils\Authentication;

use Espo\Core\Exceptions\Error;

use Espo\Entities\AuthToken;

use Espo\Core\Api\Request;

class Espo extends Base
{
    public function login(string $username, ?string $password, ?AuthToken $authToken, array $params, Request $request)
    {
        if (!$password) return;

        if ($authToken) {
            $hash = $authToken->get('hash');
        } else {
            $hash = $this->getPasswordHash()->hash($password);
        }

        $user = $this->getEntityManager()->getRepository('User')->findOne([
            'whereClause' => [
                'userName' => $username,
                'password' => $hash,
                'type!=' => ['api', 'system'],
            ]
        ]);

        if ($user && $authToken) {
            if ($user->id !== $authToken->get('userId')) {
                return null;
            }
        }

        return $user;
    }
}
