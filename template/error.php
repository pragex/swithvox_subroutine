<html lang="fr">
  <head>
    <!-- Required meta tags -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="../css/styles.css" rel="stylesheet">
    <title>Mauvaise requête</title>
  </head>
  <body>
    <section>
        <h1>Corrigé les erreurs suivantes</h1>
        <h3>Des erreur ont été détecté dans votre requête</h3>
        <table class="table">
            <thead>
            <tr>
                <th>Nom</th>
                <th>Description</th>
                <th>Erreur reporté</th>
            </tr>
            </thead>
            <tbody>
                <?php foreach($form->fields() as $name) : ?>
                <tr>
                    <td class="center"><?= $name ?></td>
                    <td><?= $form->field($name)['desc'] ?></td>
                    <?php if(array_key_exists($name, $form->errorInFields)): ?>
                        <td class="error"><?= $form->errorInFields[$name] ?></td>
                    <?php else: ?>
                        <td class="center"></td>
                    <?php endif; ?>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </section>
  </body>
</html>
