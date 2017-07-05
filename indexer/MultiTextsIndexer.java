import java.io.File;
import java.io.BufferedReader;
import java.io.InputStreamReader;
import java.io.FileInputStream;
import java.util.Date;
import java.util.regex.Pattern;
import java.util.regex.Matcher;
import org.apache.lucene.store.Directory;
import org.apache.lucene.store.FSDirectory;
import org.apache.lucene.analysis.Analyzer;
import org.apache.lucene.analysis.standard.StandardAnalyzer;
import org.apache.lucene.index.IndexWriterConfig;
import org.apache.lucene.index.IndexWriter;
import org.apache.lucene.document.Document;
import org.apache.lucene.document.Field;
import org.apache.lucene.document.TextField;

public class MultiTextsIndexer {
	private String source;

	public static void main(String[] args) throws Exception {
		MultiTextsIndexer multiTextsIndexer = new MultiTextsIndexer();
		if (!multiTextsIndexer.parseArgs(args)) return;

		File dataDir = new File("/home/ssd/StockInsider/text/" + multiTextsIndexer.source);
		File[] dataFiles = dataDir.listFiles();
		File indexDir = new File("/home/ssd/StockInsider/index/" + multiTextsIndexer.source);
		Directory directory = FSDirectory.open(indexDir.toPath());
		Analyzer analyzer = new StandardAnalyzer();
		IndexWriterConfig config = new IndexWriterConfig(analyzer);
		IndexWriter indexWriter = new IndexWriter(directory, config);
		Pattern titlePattern = Pattern.compile("\\[(.*?)\\]");
		Pattern contentPattern = Pattern.compile("\\{(.*?)\\}");
		Matcher titleMatcher, contentMatcher;
		long startTime = new Date().getTime();

		for (int i = 0; i < dataFiles.length; i++) {
			if (dataFiles[i].isFile()) {
				BufferedReader reader = new BufferedReader(new InputStreamReader(new FileInputStream(dataFiles[i]), multiTextsIndexer.source.equals("announcements_companies") ? "GB2312" : "UTF-8"));
				String lineContent, fileContent = "";
				while ((lineContent = reader.readLine()) != null) fileContent += lineContent;

				titleMatcher = titlePattern.matcher(fileContent);
				contentMatcher = contentPattern.matcher(fileContent);
				if (titleMatcher.find() && contentMatcher.find()) {
					String date;
					if (multiTextsIndexer.source.equals("announcements_companies")) date = dataFiles[i].getName().split("_")[2];
            		else {
                		String datefix = dataFiles[i].getName().split("_")[0];
                		date = datefix.substring(1, 5) + "-" + datefix.substring(5, 7) + "-" + datefix.substring(7, 9);
            		}

					String title = titleMatcher.group(0);
					title = title.substring(1, titleMatcher.group(0).length() - 1);
					title = (title.indexOf("新浪") == -1) ? title : title.substring(0, title.indexOf("新浪"));

					String content = contentMatcher.group(0);
            		content = content.substring(1, contentMatcher.group(0).length() - 1);
					
					Document document = new Document();
					document.add(new Field("path", dataFiles[i].getCanonicalPath(), TextField.TYPE_STORED));
					document.add(new Field("date", date, TextField.TYPE_STORED));
					document.add(new Field("title", title, TextField.TYPE_STORED));
					document.add(new Field("content", content, TextField.TYPE_STORED));
					indexWriter.addDocument(document);
					indexWriter.flush();

					System.out.println("已为《" + title + "》建立索引");
				}
			}
		}

		indexWriter.close();
		directory.close();

		long endTime = new Date().getTime();

		System.out.println("索引建立完成，用时" + (endTime - startTime) + "毫秒。");
	}

	private boolean parseArgs(String[] args) throws Exception {
        if (args.length != 1) {
            showTips();
            return false;
        }

        if (args[0].equals("-announcements_companies")) this.source = "announcements_companies";
        else if (args[0].equals("-announcements_CSRCRC")) this.source = "announcements_CSRCRC";
        else if (args[0].equals("-replies_CSRC")) this.source = "replies_CSRC";
        else {
            showTips();
            return false;
        }

        return true;
    }

    private void showTips() {
        System.out.println("用法：java SingleTextIndexer -source");
        System.out.println("数据源source：当前支持参数announcements_companies、announcements_CSRCRC、replies_CSRC");
    }
}
