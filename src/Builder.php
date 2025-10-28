<?php

namespace MetaRush\DataAccess;

class Builder extends Config
{
    public function build(): DataAccess
    {
        // __NAMESPACE__ or fully qualified namespace is required for dynamic use
        $adapter = __NAMESPACE__ . '\Adapters\\' . $this->getAdapter();

        // comment is for phpstan
        /** @var \MetaRush\DataAccess\Adapters\AdapterInterface $adapter  */
        $adapter = new $adapter($this);

        return new DataAccess($adapter);
    }

}