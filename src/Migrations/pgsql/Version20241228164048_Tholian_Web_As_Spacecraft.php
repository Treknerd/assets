<?php

declare(strict_types=1);

namespace Stu\Migrations\Pgsql;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20241228164048_Tholian_Web_As_Spacecraft extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Convert tholian web to spacecraft subtype';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE stu_spacecraft DROP CONSTRAINT FK_4BD20E2E73D3801E');
        $this->addSql('UPDATE stu_spacecraft sp SET holding_web_id = (SELECT ship_id FROM stu_tholian_web tw WHERE tw.id = sp.holding_web_id)');

        $this->addSql('ALTER TABLE stu_tholian_web DROP CONSTRAINT fk_d032f9a0c256317d');
        $this->addSql('DROP INDEX tholian_web_ship_idx');
        $this->addSql('ALTER TABLE stu_tholian_web ALTER id DROP IDENTITY');
        $this->addSql('UPDATE stu_tholian_web SET id = ship_id');
        $this->addSql('UPDATE stu_spacecraft sp SET type = \'THOLIAN_WEB\' WHERE EXISTS (SELECT * FROM stu_tholian_web WHERE id = sp.id)');
        $this->addSql('ALTER TABLE stu_tholian_web DROP ship_id');
        $this->addSql('ALTER TABLE stu_tholian_web ADD CONSTRAINT FK_D032F9A0BF396750 FOREIGN KEY (id) REFERENCES stu_spacecraft (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('UPDATE stu_rumps_categories SET type = \'THOLIAN_WEB\' WHERE name = \'Energienetz\'');

        $this->addSql('ALTER TABLE stu_spacecraft ADD CONSTRAINT FK_4BD20E2E73D3801E FOREIGN KEY (holding_web_id) REFERENCES stu_tholian_web (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE stu_spacecraft DROP CONSTRAINT FK_4BD20E2E73D3801E');

        $this->addSql('ALTER TABLE stu_tholian_web DROP CONSTRAINT FK_D032F9A0BF396750');
        $this->addSql('ALTER TABLE stu_tholian_web ADD ship_id INT NOT NULL');
        $this->addSql('UPDATE stu_tholian_web SET ship_id = id');
        $this->addSql('ALTER TABLE stu_tholian_web ALTER id ADD GENERATED BY DEFAULT AS IDENTITY');
        $this->addSql('ALTER TABLE stu_tholian_web ADD CONSTRAINT fk_d032f9a0c256317d FOREIGN KEY (ship_id) REFERENCES stu_ship (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX tholian_web_ship_idx ON stu_tholian_web (ship_id)');
        $this->addSql('UPDATE stu_rumps_categories SET type = \'SHIP\' WHERE type = \'THOLIAN_WEB\'');

        $this->addSql('ALTER TABLE stu_spacecraft ADD CONSTRAINT FK_4BD20E2E73D3801E FOREIGN KEY (holding_web_id) REFERENCES stu_tholian_web (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
    }
}
